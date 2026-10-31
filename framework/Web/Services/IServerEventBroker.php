<?php

/**
 * IServerEventBroker interface file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services;

/**
 * IServerEventBroker interface
 *
 * IServerEventBroker carries server events from a publisher to the streams of
 * {@see TServerEventService}. A publisher calls {@see publish()} from any request,
 * cron task, or daemon; a stream calls {@see receive()} to wait for the events of
 * its channel that follow the last one it sent.
 *
 * Each channel keeps a short buffer of recent events with ordered event IDs. An
 * event ID is opaque to callers. The buffer lets a reconnecting browser resume
 * from its `Last-Event-ID`.
 *
 * | Implementation | Waits by | Reaches |
 * |---|---|---|
 * | {@see TServerEventCacheBroker} | polling an {@see \Prado\Caching\ICache} | every process sharing the cache |
 * | {@see TServerEventMemoryBroker} | suspending on the event loop | the streams of its own process |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
interface IServerEventBroker
{
	/**
	 * Publishes an event on a channel.
	 * @param string $channel The channel name.
	 * @param string $event The event name the subscribers filter on.
	 * @param mixed $data JSON-encodable data sent with the event.
	 * @return TServerEvent The published event, with its event ID.
	 */
	public function publish(string $channel, string $event, mixed $data = null): TServerEvent;

	/**
	 * Returns the ID of the latest event of a channel.
	 * @param string $channel The channel name.
	 * @return string The latest event ID, or '' when the channel buffers no event.
	 */
	public function getLastEventId(string $channel): string;

	/**
	 * Waits for the events of a channel that follow an event ID.
	 * An empty or unknown event ID selects every buffered event.
	 * @param string $channel The channel name.
	 * @param string $lastEventId The ID of the last event the stream sent.
	 * @param float $timeout The most seconds to wait for an event.
	 * @return TServerEvent[] The events in order; empty when none arrived in time.
	 */
	public function receive(string $channel, string $lastEventId, float $timeout): array;
}
