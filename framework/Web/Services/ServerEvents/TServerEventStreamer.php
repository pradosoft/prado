<?php

/**
 * TServerEventStreamer class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\ServerEvents;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TComponent;
use Prado\TPropertyValue;
use Prado\Util\Clock\TMonotonicClockAwareTrait;
use Prado\Web\THttpHeaderName;
use Prado\Web\TMediaType;

/**
 * TServerEventStreamer class
 *
 * TServerEventStreamer writes the events of one channel from an {@see IServerEventBroker} to an
 * {@see IServerEventSink} as a `text/event-stream`. It holds the stream format and timing and
 * no request state, so one streamer serves any number of streams, one {@see run()} each.
 *
 * | Host | Caller |
 * |---|---|
 * | a PHP SAPI request | {@see TServerEventService}, with a {@see TServerEventResponseSink} |
 * | a server on the event loop, such as an HTTP/2 server | the server's request handler, in a fiber per stream, with a sink over its response stream |
 *
 * A host checks the channel name with {@see TServerEventBroker::isValidName()} and the token with
 * {@see TServerEventService::verifyChannelToken()}, and resolves the resume point with
 * {@see resolveLastEventId()}, before it calls {@see run()}.
 *
 * ```php
 * $streamer = new TServerEventStreamer();
 * $streamer->setMaxLifetime(300);
 * $lastId = TServerEventStreamer::resolveLastEventId($broker, $channel, $lastEventIdHeader);
 * TAsync::run(fn () => $streamer->run($sink, $broker, $channel, $lastId));
 * ```
 *
 * **Stream.** {@see run()} opens the sink with {@see getHeaders()}, sends `retry:`
 * {@see getRetryInterval RetryInterval}, sends each event as {@see formatEvent()}, and sends a
 * `: heartbeat` comment after {@see getHeartbeatInterval HeartbeatInterval} quiet seconds. It ends
 * after {@see getMaxLifetime MaxLifetime} seconds or when the sink reports a disconnect, then
 * closes the sink. The lifetime and heartbeat spacing are elapsed time from the
 * {@see getMonotonicClock() monotonic clock}, so a wall-clock change does not lengthen or
 * shorten them. The wait is the broker's {@see IServerEventBroker::receive()}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TServerEventStreamer extends TComponent
{
	use TMonotonicClockAwareTrait;

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
	 * Returns the response headers of a stream.
	 * They carry no connection-specific field (RFC 9113 §8.2.2), so an HTTP/2 response sends them as given.
	 * `X-Accel-Buffering: no` turns off nginx response buffering.
	 * @return array<string, string> The header values by field name.
	 */
	public static function getHeaders(): array
	{
		return [
			THttpHeaderName::ContentType => TMediaType::EVENT_STREAM . '; charset=UTF-8',
			THttpHeaderName::CacheControl => 'no-cache',
			'X-Accel-Buffering' => 'no',
		];
	}

	/**
	 * Formats an event as an `id:` and `data:` frame.
	 * The data line is JSON `{"channel": channel, "event": name, "data": data}`: the
	 * {@see TServerEvent::toArray() payload} without its `id`, which is the `id:` field.
	 * @param TServerEvent $event The event.
	 * @return string The frame, ending in a blank line.
	 */
	public static function formatEvent(TServerEvent $event): string
	{
		$payload = $event->toArray();
		unset($payload['id']);
		$data = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
		return 'id: ' . $event->getID() . "\ndata: " . $data . "\n\n";
	}

	/**
	 * Resolves the event ID a stream resumes after.
	 * @param IServerEventBroker $broker The broker.
	 * @param string $channel The channel name.
	 * @param ?string $header The `Last-Event-ID` request header; null when absent.
	 * @param ?string $parameter The event ID the page rendered; null when absent.
	 * @return string The header, else the parameter, else the channel's latest event ID.
	 */
	public static function resolveLastEventId(IServerEventBroker $broker, string $channel, ?string $header = null, ?string $parameter = null): string
	{
		return $header ?? $parameter ?? $broker->getLastEventId($channel);
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
		$this->_maxLifetime = $this->ensurePositive($value, 'servereventstreamer_maxlifetime_invalid');
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
		$this->_heartbeatInterval = $this->ensurePositive($value, 'servereventstreamer_heartbeatinterval_invalid');
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
			throw new TInvalidDataValueException('servereventstreamer_retryinterval_invalid', $value);
		}
		$this->_retryInterval = $value;
	}

	/**
	 * Streams the events of a channel to a sink until {@see getMaxLifetime MaxLifetime} passes or the client leaves.
	 * @param IServerEventSink $sink The response to write to.
	 * @param IServerEventBroker $broker The broker.
	 * @param string $channel The valid channel name.
	 * @param string $lastEventId The event ID to resume after.
	 * @return string The ID of the last event sent, or `$lastEventId` when none was sent.
	 */
	public function run(IServerEventSink $sink, IServerEventBroker $broker, string $channel, string $lastEventId): string
	{
		$sink->open(static::getHeaders());
		try {
			return $this->stream($sink, $broker, $channel, $lastEventId);
		} finally {
			$sink->close();
		}
	}

	/**
	 * Sends `retry:`, the events, and the heartbeats of a stream.
	 * @param IServerEventSink $sink The response to write to.
	 * @param IServerEventBroker $broker The broker.
	 * @param string $channel The channel name.
	 * @param string $lastEventId The event ID to resume after.
	 * @return string The ID of the last event sent, or `$lastEventId` when none was sent.
	 */
	protected function stream(IServerEventSink $sink, IServerEventBroker $broker, string $channel, string $lastEventId): string
	{
		$clock = $this->getMonotonicClock();
		$deadline = $clock->seconds() + $this->getMaxLifetime();
		$heartbeat = $this->getHeartbeatInterval();
		$lastWrite = $clock->seconds();
		if (!$sink->write('retry: ' . $this->getRetryInterval() . "\n\n")) {
			return $lastEventId;
		}
		while (($now = $clock->seconds()) < $deadline) {
			$wait = min($deadline - $now, $lastWrite + $heartbeat - $now);
			$events = $wait > 0 ? $broker->receive($channel, $lastEventId, $wait) : [];
			if ($events !== []) {
				$chunk = '';
				foreach ($events as $event) {
					$chunk .= static::formatEvent($event);
					$lastEventId = $event->getID();
				}
			} elseif ($clock->seconds() - $lastWrite >= $heartbeat) {
				$chunk = ": heartbeat\n\n";
			} else {
				continue;
			}
			$lastWrite = $clock->seconds();
			if (!$sink->write($chunk)) {
				break;
			}
		}
		return $lastEventId;
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
