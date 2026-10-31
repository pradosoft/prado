<?php

/**
 * TServerEventService class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TPropertyValue;
use Prado\TService;
use Prado\Util\Clock\TApplicationClockAwareTrait;
use Prado\Web\THttpHeaderName;
use Prado\Web\TMediaType;

/**
 * TServerEventService class
 *
 * TServerEventService streams the events of one channel to a browser `EventSource` as
 * `text/event-stream` ({@see TMediaType::EVENT_STREAM}). The events come from an
 * {@see IServerEventBroker} module, named by {@see getBrokerID BrokerID} or found by type.
 * {@see \Prado\Web\UI\ActiveControls\TServerEventTriggeredCallback} subscribes a page to a
 * channel; each event it receives raises a regular callback of that control.
 *
 * ```xml
 * <modules>
 *     <module id="cache" class="Prado\Caching\TRedisCache" />
 *     <module id="serverevents" class="Prado\Web\Services\TServerEventCacheBroker" />
 * </modules>
 * <services>
 *     <service id="events" class="Prado\Web\Services\TServerEventService" MaxLifetime="30" />
 * </services>
 * ```
 *
 * A publisher reaches the broker module from any request or task:
 * ```php
 * $this->getApplication()->getModule('serverevents')->publish('orders', 'changed', ['id' => $id]);
 * ```
 *
 * **URL and authorization.** The stream URL is `?events=<channel>&token=<token>`, built by
 * {@see constructChannelUrl()}. The token is an HMAC of the channel and the current user
 * name keyed by the {@see \Prado\Security\TSecurityManager::getValidationKey() validation key}.
 * A page that renders the URL grants its user that channel; another user, or a changed
 * channel, gets `403 Forbidden`. An invalid channel name gets `404 Not Found`. A browser
 * `EventSource` does not reconnect after either.
 *
 * **Stream.** Before the first byte the service closes the PHP session, so the session lock
 * does not hold the user's other requests, turns off output compression and every removable
 * output buffer, and sends `Cache-Control: no-cache` and `X-Accel-Buffering: no`. The stream
 * starts with `retry:` {@see getRetryInterval RetryInterval}, sends each event as `id:` and
 * a `data:` line of JSON `{"event": name, "data": data}`, and sends a `: heartbeat` comment
 * after {@see getHeartbeatInterval HeartbeatInterval} quiet seconds. It ends after
 * {@see getMaxLifetime MaxLifetime} seconds or when the client disconnects. The browser then
 * reconnects with `Last-Event-ID` and resumes after its last event.
 *
 * **Resume point.** A `Last-Event-ID` request header, else the `lastEventId` query parameter
 * (the ID the page rendered), else the channel's latest event ID at connect time.
 *
 * Under PHP-FPM or mod_php each open stream holds a worker for up to MaxLifetime seconds.
 * Size the pool for the open pages, or keep {@see \Prado\Web\UI\ActiveControls\TTimeTriggeredCallback}
 * polling where a held worker per page is too costly. Over HTTP/1.1 a browser opens at most
 * six connections per host, and each channel of a page takes one.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TServerEventService extends TService
{
	use TApplicationClockAwareTrait;
	use TServerEventBrokerIDTrait;

	/** The default service ID. */
	public const SERVICE_ID = 'events';

	/** The query parameter of the channel token. */
	public const TOKEN_PARAMETER = 'token';

	/** The query parameter of the event ID to resume after. */
	public const LAST_EVENT_ID_PARAMETER = 'lastEventId';

	/** The request header of the event ID to resume after. */
	public const LAST_EVENT_ID_HEADER = 'Last-Event-ID';

	/** The default seconds a stream stays open. */
	public const DEFAULT_MAX_LIFETIME = 30.0;

	/** The default quiet seconds before a heartbeat comment. */
	public const DEFAULT_HEARTBEAT_INTERVAL = 15.0;

	/** The default milliseconds the browser waits before reconnecting. */
	public const DEFAULT_RETRY_INTERVAL = 3000;

	/** @var float The seconds a stream stays open. */
	private float $_maxLifetime = self::DEFAULT_MAX_LIFETIME;

	/** @var float The quiet seconds before a heartbeat comment. */
	private float $_heartbeatInterval = self::DEFAULT_HEARTBEAT_INTERVAL;

	/** @var int The milliseconds the browser waits before reconnecting. */
	private int $_retryInterval = self::DEFAULT_RETRY_INTERVAL;

	/**
	 * Computes the token that grants the current user a channel.
	 * @param string $channel The channel name.
	 * @param ?TApplication $app The application; defaults to {@see Prado::getApplication()}.
	 * @return string The hex HMAC of the channel and the user name.
	 */
	public static function createChannelToken(string $channel, ?TApplication $app = null): string
	{
		$app ??= Prado::getApplication();
		$security = $app->getSecurityManager();
		$user = $app->getUser()?->getName() ?? '';
		return hash_hmac($security->getHashAlgorithm(), "prado-serverevent\0" . $channel . "\0" . $user, $security->getValidationKey());
	}

	/**
	 * Builds the stream URL of a channel for the current user.
	 * @param string $channel The channel name.
	 * @param string $serviceID The ID of the TServerEventService.
	 * @param ?TApplication $app The application; defaults to {@see Prado::getApplication()}.
	 * @return string The URL, with unencoded ampersands.
	 */
	public static function constructChannelUrl(string $channel, string $serviceID = self::SERVICE_ID, ?TApplication $app = null): string
	{
		$app ??= Prado::getApplication();
		$token = static::createChannelToken($channel, $app);
		return $app->getRequest()->constructUrl($serviceID, $channel, [self::TOKEN_PARAMETER => $token], false);
	}

	/**
	 * @return float The seconds a stream stays open. Defaults to 30.
	 */
	public function getMaxLifetime(): float
	{
		return $this->_maxLifetime;
	}

	/**
	 * @param float|string $value The seconds a stream stays open, greater than 0.
	 * @throws TInvalidDataValueException When the value is not positive.
	 */
	public function setMaxLifetime($value): void
	{
		$this->_maxLifetime = $this->ensurePositive($value, 'servereventservice_maxlifetime_invalid');
	}

	/**
	 * @return float The quiet seconds before a heartbeat comment. Defaults to 15.
	 */
	public function getHeartbeatInterval(): float
	{
		return $this->_heartbeatInterval;
	}

	/**
	 * @param float|string $value The quiet seconds before a heartbeat comment, greater than 0.
	 * @throws TInvalidDataValueException When the value is not positive.
	 */
	public function setHeartbeatInterval($value): void
	{
		$this->_heartbeatInterval = $this->ensurePositive($value, 'servereventservice_heartbeatinterval_invalid');
	}

	/**
	 * @return int The milliseconds the browser waits before reconnecting. Defaults to 3000.
	 */
	public function getRetryInterval(): int
	{
		return $this->_retryInterval;
	}

	/**
	 * @param int|string $value The milliseconds the browser waits before reconnecting, at least 0.
	 * @throws TInvalidDataValueException When the value is negative.
	 */
	public function setRetryInterval($value): void
	{
		$value = TPropertyValue::ensureInteger($value);
		if ($value < 0) {
			throw new TInvalidDataValueException('servereventservice_retryinterval_invalid', $value);
		}
		$this->_retryInterval = $value;
	}

	/**
	 * Streams the events of the requested channel.
	 * Sends `404` for an invalid channel name and `403` for a token that does not grant it.
	 */
	public function run(): void
	{
		$request = $this->getRequest();
		$response = $this->getResponse();
		$channel = TPropertyValue::ensureString($request->getServiceParameter());
		if (!TServerEventBroker::isValidName($channel)) {
			$response->setStatusCode(404);
			return;
		}
		if (!$this->authorizeChannel($channel, TPropertyValue::ensureString($request->itemAt(self::TOKEN_PARAMETER)))) {
			$response->setStatusCode(403);
			return;
		}
		$broker = $this->resolveBroker();
		$lastEventId = $this->resolveLastEventId($broker, $channel);
		$this->beginStream();
		$this->stream($broker, $channel, $lastEventId);
	}

	/**
	 * Formats an event as an `id:` and `data:` frame.
	 * @param TServerEvent $event The event.
	 * @return string The frame, ending in a blank line.
	 */
	public function formatEvent(TServerEvent $event): string
	{
		$data = json_encode(['event' => $event->getName(), 'data' => $event->getData()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
		return 'id: ' . $event->getID() . "\ndata: " . $data . "\n\n";
	}

	/**
	 * Returns whether a token grants the current user a channel.
	 * Override to add an application check, such as a channel the user may no longer see.
	 * @param string $channel The valid channel name.
	 * @param string $token The token of the request.
	 * @return bool Whether the stream may open.
	 */
	protected function authorizeChannel(string $channel, string $token): bool
	{
		return $token !== '' && hash_equals(static::createChannelToken($channel, $this->getApplication()), $token);
	}

	/**
	 * Resolves the event ID the stream resumes after.
	 * @param IServerEventBroker $broker The broker.
	 * @param string $channel The channel name.
	 * @return string The `Last-Event-ID` header, else the `lastEventId` query parameter, else
	 *   the channel's latest event ID.
	 */
	protected function resolveLastEventId(IServerEventBroker $broker, string $channel): string
	{
		$request = $this->getRequest();
		if (($header = $request->getHeader(self::LAST_EVENT_ID_HEADER)) !== null) {
			return TPropertyValue::ensureString($header);
		}
		if ($request->contains(self::LAST_EVENT_ID_PARAMETER)) {
			return TPropertyValue::ensureString($request->itemAt(self::LAST_EVENT_ID_PARAMETER));
		}
		return $broker->getLastEventId($channel);
	}

	/**
	 * Sets the stream headers, releases the session, and opens unbuffered output.
	 */
	protected function beginStream(): void
	{
		$response = $this->getResponse();
		$response->setContentType(TMediaType::EVENT_STREAM);
		$response->setCharset('UTF-8');
		$response->appendHeader(THttpHeaderName::CacheControl . ': no-cache');
		$response->appendHeader('X-Accel-Buffering: no');
		$this->releaseSession();
		$this->openOutput();
	}

	/**
	 * Writes and closes the PHP session, so its lock does not hold the user's other requests.
	 */
	protected function releaseSession(): void
	{
		if (session_status() !== PHP_SESSION_ACTIVE) {
			return;
		}
		$session = $this->getApplication()->getSession();
		if ($session->getIsStarted()) {
			$session->close();
		} else {
			session_write_close();
		}
	}

	/**
	 * Turns off output compression and the removable output buffers, sends the headers, and
	 * keeps the request running after a disconnect, so the stream ends through {@see isClientConnected()}.
	 */
	protected function openOutput(): void
	{
		if (!headers_sent()) {
			ini_set('zlib.output_compression', '0');
		}
		ignore_user_abort(true);
		if (function_exists('set_time_limit')) {
			set_time_limit((int) ceil($this->getMaxLifetime()) + 30);
		}
		$this->getResponse()->flush(false);
		while (($status = ob_get_status()) !== [] && ($status['flags'] & PHP_OUTPUT_HANDLER_REMOVABLE)) {
			ob_end_flush();
		}
	}

	/**
	 * Sends the events of a channel until {@see getMaxLifetime MaxLifetime} passes or the client leaves.
	 * @param IServerEventBroker $broker The broker.
	 * @param string $channel The channel name.
	 * @param string $lastEventId The event ID to resume after.
	 */
	protected function stream(IServerEventBroker $broker, string $channel, string $lastEventId): void
	{
		$clock = $this->getClock();
		$deadline = $clock->microtime() + $this->getMaxLifetime();
		$heartbeat = $this->getHeartbeatInterval();
		$lastWrite = $clock->microtime();
		if (!$this->sendChunk('retry: ' . $this->getRetryInterval() . "\n\n")) {
			return;
		}
		while (($now = $clock->microtime()) < $deadline) {
			$wait = min($deadline - $now, $lastWrite + $heartbeat - $now);
			$events = $wait > 0 ? $broker->receive($channel, $lastEventId, $wait) : [];
			if ($events !== []) {
				$chunk = '';
				foreach ($events as $event) {
					$chunk .= $this->formatEvent($event);
					$lastEventId = $event->getID();
				}
			} elseif ($clock->microtime() - $lastWrite >= $heartbeat) {
				$chunk = ": heartbeat\n\n";
			} else {
				continue;
			}
			$lastWrite = $clock->microtime();
			if (!$this->sendChunk($chunk)) {
				return;
			}
		}
	}

	/**
	 * Writes a chunk of the stream and flushes it to the client.
	 * @param string $chunk The chunk.
	 * @return bool Whether the client is still connected.
	 */
	protected function sendChunk(string $chunk): bool
	{
		$response = $this->getResponse();
		$response->write($chunk);
		$response->flush();
		return $this->isClientConnected();
	}

	/**
	 * @return bool Whether the client is still connected.
	 */
	protected function isClientConnected(): bool
	{
		return connection_aborted() === 0;
	}

	/**
	 * Converts a property value to a positive float.
	 * @param float|string $value The value.
	 * @param string $errorCode The error code for a value that is not positive.
	 * @throws TInvalidDataValueException When the value is not positive.
	 * @return float The value.
	 */
	private function ensurePositive($value, string $errorCode): float
	{
		$value = TPropertyValue::ensureFloat($value);
		if ($value <= 0) {
			throw new TInvalidDataValueException($errorCode, $value);
		}
		return $value;
	}
}
