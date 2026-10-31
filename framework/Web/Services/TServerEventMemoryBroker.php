<?php

/**
 * TServerEventMemoryBroker class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services;

use Prado\Exceptions\TCancelledException;
use Prado\IO\Async\TAsync;
use Prado\IO\Async\TDeferred;

/**
 * TServerEventMemoryBroker class
 *
 * TServerEventMemoryBroker keeps each channel's event buffer in the process. A stream
 * waiting in {@see receive()} suspends on the {@see TAsync::getLoop() event loop}, and a
 * {@see publish()} in the same process wakes it at once. It serves a long-running process
 * that runs its streams in fibers on one loop and publishes from that process, such as an
 * event-loop server hosting the application, and tests.
 *
 * Under PHP-FPM, mod_php, or the built-in server each request is its own process, so an
 * event published by one request never reaches another request's stream. Use
 * {@see TServerEventCacheBroker} there.
 *
 * ```php
 * $broker = new TServerEventMemoryBroker();
 * TAsync::run(fn () => $broker->receive('orders', '', 30.0));   // waits on the loop
 * $broker->publish('orders', 'changed', ['id' => 7]);            // wakes the waiting fiber
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TServerEventMemoryBroker extends TServerEventBroker
{
	/** @var array<string, array> The buffer state of each channel. */
	private array $_states = [];

	/** @var array<string, array<int, TDeferred>> The waiting receivers of each channel. */
	private array $_waiters = [];

	/**
	 * Returns the buffered events that follow the event ID, or suspends on the loop until a
	 * publish on the channel or the timeout.
	 * @param string $channel The channel name.
	 * @param string $lastEventId The ID of the last event the stream sent.
	 * @param float $timeout The most seconds to wait for an event.
	 * @return TServerEvent[] The events in order; empty when none arrived in time.
	 */
	public function receive(string $channel, string $lastEventId, float $timeout): array
	{
		$events = $this->eventsAfter($this->loadState($channel), $channel, $lastEventId);
		if ($events !== [] || $timeout <= 0) {
			return $events;
		}
		$deferred = new TDeferred();
		$this->_waiters[$channel][spl_object_id($deferred)] = $deferred;
		try {
			TAsync::timeout($deferred->getFuture(), $timeout)->await();
		} catch (TCancelledException $e) {
			return [];
		} finally {
			unset($this->_waiters[$channel][spl_object_id($deferred)]);
			if (empty($this->_waiters[$channel])) {
				unset($this->_waiters[$channel]);
			}
		}
		return $this->eventsAfter($this->loadState($channel), $channel, $lastEventId);
	}

	/**
	 * Returns the number of receivers waiting on a channel.
	 * @param string $channel The channel name.
	 * @return int The number of waiting receivers.
	 */
	public function getWaiterCount(string $channel): int
	{
		return count($this->_waiters[$channel] ?? []);
	}

	/**
	 * Appends an event to the channel's buffer and wakes its waiting receivers.
	 * @param string $channel The valid channel name.
	 * @param string $event The valid event name.
	 * @param mixed $data The JSON-encodable event data.
	 * @return TServerEvent The stored event.
	 */
	protected function storeEvent(string $channel, string $event, mixed $data): TServerEvent
	{
		[$state, $published] = $this->appendEvent($this->loadState($channel), $channel, $event, $data);
		$this->_states[$channel] = $state;
		foreach ($this->_waiters[$channel] ?? [] as $deferred) {
			if (!$deferred->isComplete()) {
				$deferred->resolve();
			}
		}
		return $published;
	}

	/**
	 * Returns the channel's state from the process. A state whose events have all expired
	 * is dropped, so an idle channel holds no memory.
	 * @param string $channel The channel name.
	 * @return ?array The state, or null when the channel has none.
	 */
	protected function loadState(string $channel): ?array
	{
		$state = $this->pruneState($this->_states[$channel] ?? null);
		if ($state !== null && $state['events'] === []) {
			unset($this->_states[$channel]);
			return null;
		}
		return $state;
	}
}
