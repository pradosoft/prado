<?php

/**
 * TCurlMultiHttpHandler class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\IO\HttpClient;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Async\IEventLoop;
use Prado\IO\Async\TAsync;
use Prado\IO\Async\TCancellationToken;
use Prado\IO\Async\TDeferred;
use Prado\IO\Async\TFuture;
use Prado\TPropertyValue;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * TCurlMultiHttpHandler class.
 *
 * TCurlMultiHttpHandler runs its transfers on a cURL multi handle driven from the event
 * loop, so several transfers proceed at once and a fiber that waits for a response yields
 * to the other fibers.  {@see handleAsync()} returns the {@see TFuture} of the response;
 * {@see handle()} awaits it, which from a worker fiber yields to the loop and from the
 * main fiber runs the loop.  {@see THttpClient} chooses this handler for a request sent
 * from inside a fiber when cURL is loaded.
 * ```php
 * $handler = new TCurlMultiHttpHandler();
 * $a = $handler->handleAsync(new TRequest('GET', 'https://api.example.com/a'));
 * $b = $handler->handleAsync(new TRequest('GET', 'https://api.example.com/b'));
 * [$a, $b] = TAsync::all([$a, $b])->await();
 * ```
 *
 * PHP exposes neither libcurl's socket nor its timer callbacks, so the multi handle is
 * pumped by a repeating loop timer at {@see setPollInterval PollInterval} (default 10 ms)
 * while a transfer is pending; the timer is cancelled when none is.  Every pump is a
 * non-blocking `curl_multi_exec()`.  {@see setMaxConnections MaxConnections} limits the
 * connections libcurl opens at once.  Cancelling the {@see THttpClientOption::TOKEN}
 * removes the transfer and rejects its future with the token's reason.
 *
 * libcurl's resolver thread pool does not survive a `fork()`, and a child that destroys
 * an inherited multi handle waits forever for the pool's threads.  Before
 * {@see \Prado\Util\Helpers\TProcessHelper::fork()} forks, {@see fxPrepareForFork()}
 * releases an idle multi handle in the parent, and the next transfer creates a new one; a
 * fork while a transfer is pending is not supported.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCurlMultiHttpHandler extends TCurlHttpHandler implements IAsyncHttpHandler
{
	/** The default seconds between pumps of the multi handle. */
	public const DEFAULT_POLL_INTERVAL = 0.01;

	/** @var ?\CurlMultiHandle The multi handle, created on the first transfer. */
	private ?\CurlMultiHandle $_multi = null;

	/** @var array<int, array{transfer: TCurlTransfer, deferred: TDeferred, subscription: ?string, token: ?TCancellationToken}> The pending transfers, by handle object id. */
	private array $_transfers = [];

	/** @var ?IEventLoop The loop the pump timer runs on. */
	private ?IEventLoop $_loop = null;

	/** @var ?string The pump timer's callback id. */
	private ?string $_pumpId = null;

	/** @var float The seconds between pumps of the multi handle. */
	private float $_pollInterval = self::DEFAULT_POLL_INTERVAL;

	/** @var int The maximum connections open at once, or 0 for the libcurl default. */
	private int $_maxConnections = 0;

	/**
	 * Reports whether the cURL extension with its multi interface is loaded.
	 * @return bool Whether the cURL multi interface is loaded.
	 */
	public static function getIsAvailable(): bool
	{
		return function_exists('curl_multi_init');
	}

	/**
	 * Sends a request on the multi handle and waits for its response on the event loop.
	 * @param RequestInterface $request The request.
	 * @param array<string, mixed> $options The {@see THttpClientOption} options.
	 * @throws THttpNetworkException When no response arrives.
	 * @throws THttpRequestException When the request cannot be sent.
	 * @throws \Prado\Exceptions\TCancelledException When the token is cancelled.
	 * @return ResponseInterface The response.
	 */
	public function handle(RequestInterface $request, array $options = []): ResponseInterface
	{
		return $this->handleAsync($request, $options)->await();
	}

	/**
	 * Starts a request on the multi handle and returns the future of its response.
	 * @param RequestInterface $request The request.
	 * @param array<string, mixed> $options The {@see THttpClientOption} options.
	 * @return TFuture Resolves with the response, or rejects with the exception {@see handle()} would throw.
	 */
	public function handleAsync(RequestInterface $request, array $options = []): TFuture
	{
		$deferred = new TDeferred();
		$options = $this->resolveOptions($options);
		$token = $this->getToken($options);
		if ($token !== null && $token->isCancelled()) {
			$deferred->reject($token->getReason());
			return $deferred->getFuture();
		}
		try {
			$this->assertSendable($request);
			$transfer = $this->createTransfer($request, $options);
		} catch (\Throwable $e) {
			$deferred->reject($e);
			return $deferred->getFuture();
		}
		$handle = $transfer->getHandle();
		$id = spl_object_id($handle);
		$this->_transfers[$id] = ['transfer' => $transfer, 'deferred' => $deferred, 'subscription' => null, 'token' => $token];
		$code = curl_multi_add_handle($this->getMultiHandle(), $handle);
		if ($code !== CURLM_OK) {
			unset($this->_transfers[$id]);
			$deferred->reject(new THttpNetworkException($request, 'httpclient_transport_error', $code, (string) curl_multi_strerror($code)));
			return $deferred->getFuture();
		}
		if ($token !== null) {
			$this->_transfers[$id]['subscription'] = $token->subscribe(function (\Throwable $reason) use ($id): void {
				$this->cancelTransfer($id, $reason);
			});
		}
		$this->startPump();
		$this->pump();
		return $deferred->getFuture();
	}

	/**
	 * Returns the number of transfers in progress.
	 * @return int The number of transfers in progress.
	 */
	public function getPendingCount(): int
	{
		return count($this->_transfers);
	}

	/**
	 * Returns the seconds between pumps of the multi handle while a transfer is pending.
	 * @return float The poll interval.
	 */
	public function getPollInterval(): float
	{
		return $this->_pollInterval;
	}

	/**
	 * Sets the seconds between pumps of the multi handle; applies to the next pump timer.
	 * @param float|string $seconds The poll interval.
	 * @throws TInvalidDataValueException When the seconds are not greater than zero.
	 */
	public function setPollInterval($seconds): void
	{
		$seconds = TPropertyValue::ensureFloat($seconds);
		if ($seconds <= 0) {
			throw new TInvalidDataValueException('httpclient_poll_interval_invalid', $seconds);
		}
		$this->_pollInterval = $seconds;
	}

	/**
	 * Returns the maximum connections libcurl opens at once.
	 * @return int The maximum connections, or 0 for the libcurl default.
	 */
	public function getMaxConnections(): int
	{
		return $this->_maxConnections;
	}

	/**
	 * Sets the maximum connections libcurl opens at once.
	 * @param int|string $count The maximum connections; 0 for the libcurl default.
	 */
	public function setMaxConnections($count): void
	{
		$this->_maxConnections = max(0, TPropertyValue::ensureInteger($count));
		if ($this->_multi !== null) {
			curl_multi_setopt($this->_multi, CURLMOPT_MAX_TOTAL_CONNECTIONS, $this->_maxConnections);
		}
	}

	/**
	 * Releases the multi handle before the process forks, when no transfer is pending.  The
	 * parent frees it while libcurl's threads still exist, so the child inherits no handle
	 * whose destruction would wait on them.  Raised as `fxPrepareForFork` by
	 * {@see \Prado\Util\Helpers\TProcessHelper::fork()}.
	 * @param mixed $sender The application.
	 * @param mixed $param Null.
	 * @return array Nothing to restore after the fork.
	 */
	public function fxPrepareForFork($sender, $param): array
	{
		if ($this->_transfers === []) {
			$this->stopPump();
			$this->_multi = null;
		}
		return [];
	}

	/**
	 * Returns the multi handle, creating it with {@see getMaxConnections} on first use.
	 * @return \CurlMultiHandle The multi handle.
	 */
	protected function getMultiHandle(): \CurlMultiHandle
	{
		if ($this->_multi === null) {
			$this->_multi = curl_multi_init();
			curl_multi_setopt($this->_multi, CURLMOPT_MAX_TOTAL_CONNECTIONS, $this->_maxConnections);
		}
		return $this->_multi;
	}

	/**
	 * Registers the repeating pump timer on {@see TAsync::getLoop()} when none runs.
	 */
	protected function startPump(): void
	{
		if ($this->_pumpId !== null) {
			return;
		}
		$this->_loop = TAsync::getLoop();
		$this->_pumpId = $this->_loop->repeat($this->getPollInterval(), function (): void {
			$this->pump();
		});
	}

	/**
	 * Cancels the pump timer.
	 */
	protected function stopPump(): void
	{
		if ($this->_pumpId !== null) {
			$this->_loop?->cancel($this->_pumpId);
			$this->_pumpId = null;
			$this->_loop = null;
		}
	}

	/**
	 * Advances every transfer without blocking and settles the finished ones.  A multi
	 * handle failure rejects every pending transfer.
	 */
	protected function pump(): void
	{
		if ($this->_multi === null) {
			return;
		}
		$active = 0;
		do {
			$code = curl_multi_exec($this->_multi, $active);
		} while ($code === CURLM_CALL_MULTI_PERFORM);
		if ($code !== CURLM_OK) {
			foreach (array_keys($this->_transfers) as $id) {
				$request = $this->_transfers[$id]['transfer']->getRequest();
				$this->cancelTransfer($id, new THttpNetworkException($request, 'httpclient_transport_error', $code, (string) curl_multi_strerror($code)));
			}
			return;
		}
		while (($info = curl_multi_info_read($this->_multi)) !== false) {
			$this->settleTransfer(spl_object_id($info['handle']), (int) $info['result']);
		}
		if ($this->_transfers === []) {
			$this->stopPump();
		}
	}

	/**
	 * Removes a finished transfer from the multi handle and settles its future.
	 * @param int $id The transfer id.
	 * @param int $result The `CURLE_*` result of the transfer.
	 */
	protected function settleTransfer(int $id, int $result): void
	{
		$entry = $this->detachTransfer($id);
		if ($entry === null) {
			return;
		}
		try {
			$response = $this->completeTransfer($entry['transfer'], $result);
		} catch (\Throwable $e) {
			$entry['deferred']->reject($e);
			return;
		}
		$entry['deferred']->resolve($response);
	}

	/**
	 * Removes a pending transfer from the multi handle and rejects its future.
	 * @param int $id The transfer id.
	 * @param \Throwable $reason The rejection.
	 */
	protected function cancelTransfer(int $id, \Throwable $reason): void
	{
		$entry = $this->detachTransfer($id);
		if ($entry === null) {
			return;
		}
		$entry['deferred']->reject($reason);
		if ($this->_transfers === []) {
			$this->stopPump();
		}
	}

	/**
	 * Takes a transfer out of the pending list and the multi handle, and drops its token subscription.
	 * @param int $id The transfer id.
	 * @return ?array{transfer: TCurlTransfer, deferred: TDeferred, subscription: ?string, token: ?TCancellationToken} The entry, or null when unknown.
	 */
	private function detachTransfer(int $id): ?array
	{
		if (!isset($this->_transfers[$id])) {
			return null;
		}
		$entry = $this->_transfers[$id];
		unset($this->_transfers[$id]);
		if ($entry['subscription'] !== null) {
			$entry['token']?->unsubscribe($entry['subscription']);
		}
		if ($this->_multi !== null) {
			curl_multi_remove_handle($this->_multi, $entry['transfer']->getHandle());
		}
		return $entry;
	}
}
