<?php

/**
 * TServerEventService class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\ServerEvents;

use Prado\Prado;
use Prado\TApplication;
use Prado\TPropertyValue;
use Prado\TService;
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
 *     <module id="serverevents" class="Prado\Web\Services\ServerEvents\TServerEventCacheBroker" />
 * </modules>
 * <services>
 *     <service id="events" class="Prado\Web\Services\ServerEvents\TServerEventService" MaxLifetime="30" />
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
 * **Stream.** The service closes the PHP session before the first byte, so the session lock
 * does not hold the user's other requests. Its {@see getStreamer() streamer} then writes the
 * stream to a {@see TServerEventResponseSink}: `retry:`, the events, and heartbeats, until
 * {@see getMaxLifetime MaxLifetime} passes or the client disconnects. The browser reconnects
 * with `Last-Event-ID` and resumes after its last event. A server on the event loop, such as
 * an HTTP/2 server, runs a {@see TServerEventStreamer} over its own {@see IServerEventSink}
 * and checks the token with {@see verifyChannelToken()}.
 *
 * **Resume point.** A `Last-Event-ID` request header, else the `lastEventId` query parameter
 * (the ID the page rendered), else the channel's latest event ID at connect time.
 *
 * Under PHP-FPM or mod_php each open stream holds a worker for up to MaxLifetime seconds.
 * Size the pool for the open pages, or keep {@see \Prado\Web\UI\ActiveControls\TTimeTriggeredCallback}
 * polling where a held worker per page is too costly. Over HTTP/1.1 a browser opens at most
 * six connections per host, and each channel of a page takes one. Over HTTP/2 a stream is
 * one stream of the shared connection.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TServerEventService extends TService
{
	use TServerEventBrokerIDTrait;

	/** The default service ID. */
	public const SERVICE_ID = 'events';

	/** The query parameter of the channel token. */
	public const TOKEN_PARAMETER = 'token';

	/** The query parameter of the event ID to resume after. */
	public const LAST_EVENT_ID_PARAMETER = 'lastEventId';

	/** The seconds `set_time_limit()` allows beyond MaxLifetime. */
	public const TIME_LIMIT_MARGIN = 30;

	/** @var ?TServerEventStreamer The streamer, created on first use. */
	private ?TServerEventStreamer $_streamer = null;

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
	 * Returns whether a token grants the current user a channel.
	 * @param string $channel The channel name.
	 * @param string $token The token of the request.
	 * @param ?TApplication $app The application; defaults to {@see Prado::getApplication()}.
	 * @return bool Whether the token equals {@see createChannelToken()}, compared in constant time.
	 */
	public static function verifyChannelToken(string $channel, string $token, ?TApplication $app = null): bool
	{
		return $token !== '' && hash_equals(static::createChannelToken($channel, $app), $token);
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
	 * @return TServerEventStreamer The streamer that writes the streams of this service.
	 */
	public function getStreamer(): TServerEventStreamer
	{
		return $this->_streamer ??= $this->createStreamer();
	}

	/**
	 * @return float The seconds a stream stays open. Defaults to 30.
	 */
	public function getMaxLifetime(): float
	{
		return $this->getStreamer()->getMaxLifetime();
	}

	/**
	 * @param float|string $value The seconds a stream stays open, greater than 0.
	 * @throws \Prado\Exceptions\TInvalidDataValueException When the value is not positive.
	 */
	public function setMaxLifetime($value): void
	{
		$this->getStreamer()->setMaxLifetime($value);
	}

	/**
	 * @return float The quiet seconds before a heartbeat comment. Defaults to 15.
	 */
	public function getHeartbeatInterval(): float
	{
		return $this->getStreamer()->getHeartbeatInterval();
	}

	/**
	 * @param float|string $value The quiet seconds before a heartbeat comment, greater than 0.
	 * @throws \Prado\Exceptions\TInvalidDataValueException When the value is not positive.
	 */
	public function setHeartbeatInterval($value): void
	{
		$this->getStreamer()->setHeartbeatInterval($value);
	}

	/**
	 * @return int The milliseconds the browser waits before reconnecting. Defaults to 3000.
	 */
	public function getRetryInterval(): int
	{
		return $this->getStreamer()->getRetryInterval();
	}

	/**
	 * @param int|string $value The milliseconds the browser waits before reconnecting, at least 0.
	 * @throws \Prado\Exceptions\TInvalidDataValueException When the value is negative.
	 */
	public function setRetryInterval($value): void
	{
		$this->getStreamer()->setRetryInterval($value);
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
		$this->releaseSession();
		$this->getStreamer()->run($this->createSink(), $broker, $channel, $lastEventId);
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
		return static::verifyChannelToken($channel, $token, $this->getApplication());
	}

	/**
	 * Resolves the event ID the stream resumes after, through {@see TServerEventStreamer::resolveLastEventId()}.
	 * @param IServerEventBroker $broker The broker.
	 * @param string $channel The channel name.
	 * @return string The `Last-Event-ID` header, else the `lastEventId` query parameter, else
	 *   the channel's latest event ID.
	 */
	protected function resolveLastEventId(IServerEventBroker $broker, string $channel): string
	{
		$request = $this->getRequest();
		$header = $request->getHeader(TServerEventStreamer::LAST_EVENT_ID_HEADER);
		$parameter = $request->contains(self::LAST_EVENT_ID_PARAMETER) ? $request->itemAt(self::LAST_EVENT_ID_PARAMETER) : null;
		return TServerEventStreamer::resolveLastEventId(
			$broker,
			$channel,
			$header === null ? null : TPropertyValue::ensureString($header),
			$parameter === null ? null : TPropertyValue::ensureString($parameter)
		);
	}

	/**
	 * @return TServerEventStreamer A new streamer for {@see getStreamer()}.
	 */
	protected function createStreamer(): TServerEventStreamer
	{
		return new TServerEventStreamer();
	}

	/**
	 * @return IServerEventSink The sink of this request: the response, with a time limit of
	 *   MaxLifetime plus {@see TIME_LIMIT_MARGIN} seconds.
	 */
	protected function createSink(): IServerEventSink
	{
		return new TServerEventResponseSink($this->getResponse(), (int) ceil($this->getMaxLifetime()) + self::TIME_LIMIT_MARGIN);
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
}
