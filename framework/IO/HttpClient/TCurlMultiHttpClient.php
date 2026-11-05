<?php

/**
 * TCurlMultiHttpClient class file.
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

/**
 * TCurlMultiHttpClient class.
 *
 * TCurlMultiHttpClient runs its requests on a cURL multi handle driven from the event loop,
 * so several transfers proceed at once and a fiber that waits for a response yields to the
 * other fibers.  {@see downloadAsync()} returns the {@see \Prado\IO\Async\TFuture} of the
 * response; {@see download()} awaits it, which from a worker fiber yields to the loop and
 * from the main fiber runs the loop.
 *
 * ```php
 * $client = new TCurlMultiHttpClient();
 * $a = $client->downloadAsync('GET', 'https://api.example.com/a');
 * $b = $client->downloadAsync('GET', 'https://api.example.com/b');
 * [$a, $b] = TAsync::all([$a, $b])->await();
 * ```
 *
 * PHP exposes neither libcurl's socket nor its timer callbacks, so the multi handle is
 * pumped by a repeating loop timer at {@see setPollInterval PollInterval} (default 10 ms)
 * while a transfer is pending; the timer is cancelled when none is.  Every pump is a
 * non-blocking `curl_multi_exec()`.  {@see setMaxConnections MaxConnections} limits the
 * connections libcurl opens at once.  A {@see \Prado\IO\Async\TCancellationToken} passed to
 * {@see downloadAsync()} removes the transfer and rejects its future with the reason.
 *
 * {@see TCurlHttpClient} and {@see TFopenHttpClient} stay synchronous; this client is chosen
 * explicitly and is not in {@see THttpClient::TRANSPORTS}.
 *
 * libcurl's resolver thread pool does not survive a `fork()`, and a child that destroys an
 * inherited multi handle waits forever for the pool's threads.  Before
 * {@see \Prado\Util\Helpers\TProcessHelper::fork()} forks, {@see fxPrepareForFork()} releases an
 * idle multi handle in the parent, and the next transfer creates a new one; a fork while a
 * transfer is pending is not supported.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TCurlMultiHttpClient extends TCurlHttpClient
{
	/** The default seconds between pumps of the multi handle. */
	public const DEFAULT_POLL_INTERVAL = 0.01;

	/** @var ?\CurlMultiHandle The multi handle, created on the first transfer. */
	private ?\CurlMultiHandle $_multi = null;

	/** @var array<int, array{handle: \CurlHandle, deferred: TDeferred, headers: array<string,string>, subscription: ?string, token: ?TCancellationToken}> The pending transfers, by handle object id. */
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
	 * @return bool whether the cURL extension with its multi interface is loaded.
	 */
	public static function getIsAvailable(): bool
	{
		return function_exists('curl_multi_init');
	}

	/**
	 * Performs an HTTP request, waiting for the response on the event loop.
	 * @param string $method HTTP verb.
	 * @param string $url Absolute URL to request.
	 * @param array<string,string> $headers Request headers.
	 * @param ?string $body Raw request body, or null.
	 * @throws THttpClientException on cURL-level failure.
	 * @return THttpClientResponse
	 */
	public function download(string $method, string $url, array $headers = [], ?string $body = null): THttpClientResponse
	{
		return $this->downloadAsync($method, $url, $headers, $body)->await();
	}

	/**
	 * Starts an HTTP request on the multi handle and returns the future of its response.
	 * @param string $method HTTP verb.
	 * @param string $url Absolute URL to request.
	 * @param array<string,string> $headers Request headers.
	 * @param ?string $body Raw request body, or null.
	 * @param ?TCancellationToken $token A token whose cancellation removes the transfer and rejects the future.
	 * @return TFuture Resolves with the {@see THttpClientResponse}, or rejects with a {@see THttpClientException} on a cURL-level failure.
	 */
	public function downloadAsync(string $method, string $url, array $headers = [], ?string $body = null, ?TCancellationToken $token = null): TFuture
	{
		$deferred = new TDeferred();
		if ($token !== null && $token->isCancelled()) {
			$deferred->reject($token->getReason());
			return $deferred->getFuture();
		}
		$responseHeaders = [];
		$handle = $this->createHandle($method, $url, $headers, $body, $responseHeaders);
		$id = spl_object_id($handle);
		$this->_transfers[$id] = ['handle' => $handle, 'deferred' => $deferred, 'headers' => &$responseHeaders, 'subscription' => null, 'token' => $token];
		$code = curl_multi_add_handle($this->getMultiHandle(), $handle);
		if ($code !== CURLM_OK) {
			unset($this->_transfers[$id]);
			$deferred->reject(new THttpClientException('httpclient_transport_error', $code, (string) curl_multi_strerror($code)));
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
	 * @return int The number of transfers in progress.
	 */
	public function getPendingCount(): int
	{
		return count($this->_transfers);
	}

	/**
	 * @return float The seconds between pumps of the multi handle while a transfer is pending.
	 */
	public function getPollInterval(): float
	{
		return $this->_pollInterval;
	}

	/**
	 * @param float|string $seconds The seconds between pumps of the multi handle; applies to the next pump timer.
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
	 * @return int The maximum connections libcurl opens at once, or 0 for its default.
	 */
	public function getMaxConnections(): int
	{
		return $this->_maxConnections;
	}

	/**
	 * @param int|string $count The maximum connections libcurl opens at once; 0 for its default.
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
	 * parent frees it while libcurl's threads still exist, so the child inherits no handle whose
	 * destruction would wait on them.  Raised as `fxPrepareForFork` by
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
	 * Advances every transfer without blocking and completes the finished ones.  A multi
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
			$error = new THttpClientException('httpclient_transport_error', $code, (string) curl_multi_strerror($code));
			foreach (array_keys($this->_transfers) as $id) {
				$this->cancelTransfer($id, $error);
			}
			return;
		}
		while (($info = curl_multi_info_read($this->_multi)) !== false) {
			$this->completeTransfer(spl_object_id($info['handle']), (int) $info['result']);
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
	protected function completeTransfer(int $id, int $result): void
	{
		$transfer = $this->detachTransfer($id);
		if ($transfer === null) {
			return;
		}
		$handle = $transfer['handle'];
		if ($result !== CURLE_OK) {
			$transfer['deferred']->reject(new THttpClientException('httpclient_transport_error', $result, curl_error($handle)));
			return;
		}
		$body = (string) curl_multi_getcontent($handle);
		$transfer['deferred']->resolve($this->createResponse($handle, $transfer['headers'], $body));
	}

	/**
	 * Removes a pending transfer from the multi handle and rejects its future.
	 * @param int $id The transfer id.
	 * @param \Throwable $reason The rejection.
	 */
	protected function cancelTransfer(int $id, \Throwable $reason): void
	{
		$transfer = $this->detachTransfer($id);
		if ($transfer === null) {
			return;
		}
		$transfer['deferred']->reject($reason);
		if ($this->_transfers === []) {
			$this->stopPump();
		}
	}

	/**
	 * Takes a transfer out of the pending list and the multi handle, and drops its token subscription.
	 * @param int $id The transfer id.
	 * @return ?array{handle: \CurlHandle, deferred: TDeferred, headers: array<string,string>, subscription: ?string, token: ?TCancellationToken} The transfer, or null when unknown.
	 */
	private function detachTransfer(int $id): ?array
	{
		if (!isset($this->_transfers[$id])) {
			return null;
		}
		$transfer = $this->_transfers[$id];
		unset($this->_transfers[$id]);
		if ($transfer['subscription'] !== null) {
			$transfer['token']?->unsubscribe($transfer['subscription']);
		}
		if ($this->_multi !== null) {
			curl_multi_remove_handle($this->_multi, $transfer['handle']);
		}
		return $transfer;
	}
}
