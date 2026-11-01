<?php

/**
 * TServerEventBroker class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\Services\ServerEvents;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TModule;
use Prado\TPropertyValue;
use Prado\Util\Clock\TApplicationClockAwareTrait;

/**
 * TServerEventBroker class
 *
 * TServerEventBroker is the base module of the {@see IServerEventBroker} implementations.
 * It validates names and keeps the event buffer of a channel as a state array that a
 * subclass stores: {@see TServerEventCacheBroker} in a cache, {@see TServerEventMemoryBroker}
 * in the process.
 *
 * Channel and event names match {@see NAME_PATTERN}: 1 to 128 letters, digits, `_`, `.`,
 * `:`, or `-`.
 *
 * An event ID is `<generation>-<sequence>`. The generation is random and is created with
 * the channel's buffer; the sequence counts the channel's events. An ID of another
 * generation, as after a cache flush, selects every buffered event.
 *
 * The buffer keeps at most {@see getBufferSize BufferSize} events, each for at most
 * {@see getEventLifetime EventLifetime} seconds. A browser that reconnects after its
 * last event left the buffer misses the events in between.
 *
 * After it stores an event, {@see publish()} raises the global {@see PUBLISHED_EVENT}
 * (`fxServerEventPublished`) through {@see announcePublished()}, so a module can relay every
 * published event to another transport.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
abstract class TServerEventBroker extends TModule implements IServerEventBroker
{
	use TApplicationClockAwareTrait;

	/** The pattern of a channel or event name. */
	public const NAME_PATTERN = '/^[A-Za-z0-9_.:\-]{1,128}$/';

	/** The global event raised after a broker stores an event; see {@see announcePublished()}. */
	public const PUBLISHED_EVENT = 'fxServerEventPublished';

	/** The default number of buffered events per channel. */
	public const DEFAULT_BUFFER_SIZE = 100;

	/** The default seconds an event stays buffered. */
	public const DEFAULT_EVENT_LIFETIME = 300;

	/** @var int The number of buffered events per channel. */
	private int $_bufferSize = self::DEFAULT_BUFFER_SIZE;

	/** @var int The seconds an event stays buffered. */
	private int $_eventLifetime = self::DEFAULT_EVENT_LIFETIME;

	/**
	 * Returns whether a string is a valid channel or event name.
	 * @param string $name The name.
	 * @return bool Whether the name matches {@see NAME_PATTERN}.
	 */
	public static function isValidName(string $name): bool
	{
		return preg_match(self::NAME_PATTERN, $name) === 1;
	}

	/**
	 * @return int The number of buffered events per channel. Defaults to 100.
	 */
	public function getBufferSize(): int
	{
		return $this->_bufferSize;
	}

	/**
	 * @param int|string $value The number of buffered events per channel, at least 1.
	 * @throws TInvalidDataValueException When the value is below 1.
	 */
	public function setBufferSize($value): void
	{
		$value = TPropertyValue::ensureInteger($value);
		if ($value < 1) {
			throw new TInvalidDataValueException('servereventbroker_buffersize_invalid', $value);
		}
		$this->_bufferSize = $value;
	}

	/**
	 * @return int The seconds an event stays buffered. Defaults to 300.
	 */
	public function getEventLifetime(): int
	{
		return $this->_eventLifetime;
	}

	/**
	 * @param int|string $value The seconds an event stays buffered, at least 1.
	 * @throws TInvalidDataValueException When the value is below 1.
	 */
	public function setEventLifetime($value): void
	{
		$value = TPropertyValue::ensureInteger($value);
		if ($value < 1) {
			throw new TInvalidDataValueException('servereventbroker_eventlifetime_invalid', $value);
		}
		$this->_eventLifetime = $value;
	}

	/**
	 * Publishes an event on a channel.
	 * @param string $channel The channel name.
	 * @param string $event The event name the subscribers filter on.
	 * @param mixed $data JSON-encodable data sent with the event.
	 * @throws TInvalidDataValueException When the channel or event name is invalid, or the
	 *   data is not JSON-encodable.
	 * @return TServerEvent The published event, with its event ID.
	 */
	public function publish(string $channel, string $event, mixed $data = null): TServerEvent
	{
		$this->ensureValidName($channel);
		$this->ensureValidName($event);
		if (json_encode($data) === false) {
			throw new TInvalidDataValueException('servereventbroker_data_invalid', $channel, $event, json_last_error_msg());
		}
		$published = $this->storeEvent($channel, $event, $data);
		static::announcePublished($this, $published);
		return $published;
	}

	/**
	 * Raises the global {@see PUBLISHED_EVENT} for an event a broker stored, with the broker
	 * as the sender and the {@see TServerEvent} as the parameter.  A module that relays events
	 * to another transport, such as a WebSocket cluster, listens with an `fxServerEventPublished`
	 * method.  {@see publish()} calls it after {@see storeEvent()}; a broker that implements
	 * {@see IServerEventBroker} itself calls it once the event is stored and visible to readers,
	 * which for a transactional store is after the commit.
	 * @param IServerEventBroker $broker The broker that stored the event.
	 * @param TServerEvent $event The stored event, with its event ID.
	 */
	public static function announcePublished(IServerEventBroker $broker, TServerEvent $event): void
	{
		$event->raiseEvent(static::PUBLISHED_EVENT, $broker, $event);
	}

	/**
	 * Returns the ID of the latest event of a channel.
	 * @param string $channel The channel name.
	 * @return string The latest event ID, or '' when the channel buffers no event.
	 */
	public function getLastEventId(string $channel): string
	{
		$state = $this->pruneState($this->loadState($channel));
		if ($state === null || $state['events'] === []) {
			return '';
		}
		return $this->formatEventId($state['generation'], $state['sequence']);
	}

	/**
	 * Appends an event to the stored buffer of a channel.
	 * @param string $channel The valid channel name.
	 * @param string $event The valid event name.
	 * @param mixed $data The JSON-encodable event data.
	 * @return TServerEvent The stored event.
	 */
	abstract protected function storeEvent(string $channel, string $event, mixed $data): TServerEvent;

	/**
	 * Loads the stored buffer state of a channel.
	 * @param string $channel The channel name.
	 * @return ?array The state, or null when the channel has none.
	 */
	abstract protected function loadState(string $channel): ?array;

	/**
	 * Throws when a channel or event name is invalid.
	 * @param string $name The name.
	 * @throws TInvalidDataValueException When the name does not match {@see NAME_PATTERN}.
	 */
	protected function ensureValidName(string $name): void
	{
		if (!static::isValidName($name)) {
			throw new TInvalidDataValueException('servereventbroker_name_invalid', $name);
		}
	}

	/**
	 * Creates the empty buffer state of a channel with a new generation.
	 * @return array The state: `generation`, `sequence`, and `events`.
	 */
	protected function createState(): array
	{
		return ['generation' => bin2hex(random_bytes(4)), 'sequence' => 0, 'events' => []];
	}

	/**
	 * Appends an event to a buffer state and prunes it.
	 * @param ?array $state The state, or null to create one.
	 * @param string $channel The channel name.
	 * @param string $event The event name.
	 * @param mixed $data The event data.
	 * @return array The new state and the appended event: `[array $state, TServerEvent $event]`.
	 */
	protected function appendEvent(?array $state, string $channel, string $event, mixed $data): array
	{
		$state ??= $this->createState();
		$sequence = $state['sequence'] + 1;
		$time = $this->getClock()->microtime();
		$state['sequence'] = $sequence;
		$state['events'][] = ['sequence' => $sequence, 'name' => $event, 'data' => $data, 'time' => $time];
		$state = $this->pruneState($state);
		return [$state, new TServerEvent($this->formatEventId($state['generation'], $sequence), $channel, $event, $data, $time)];
	}

	/**
	 * Removes the events past {@see getEventLifetime EventLifetime} and beyond {@see getBufferSize BufferSize}.
	 * @param ?array $state The state.
	 * @return ?array The pruned state, or null for a null state.
	 */
	protected function pruneState(?array $state): ?array
	{
		if ($state === null) {
			return null;
		}
		$oldest = $this->getClock()->microtime() - $this->getEventLifetime();
		$events = array_filter($state['events'], fn ($event) => $event['time'] >= $oldest);
		$state['events'] = array_slice(array_values($events), -$this->getBufferSize());
		return $state;
	}

	/**
	 * Returns the buffered events of a state that follow an event ID.
	 * @param ?array $state The state.
	 * @param string $channel The channel name.
	 * @param string $lastEventId The ID of the last sent event; '' or another generation selects every event.
	 * @return TServerEvent[] The events in order.
	 */
	protected function eventsAfter(?array $state, string $channel, string $lastEventId): array
	{
		$state = $this->pruneState($state);
		if ($state === null) {
			return [];
		}
		$after = 0;
		$parts = explode('-', $lastEventId, 2);
		if (count($parts) === 2 && $parts[0] === $state['generation'] && ctype_digit($parts[1])) {
			$after = (int) $parts[1];
		}
		$events = [];
		foreach ($state['events'] as $event) {
			if ($event['sequence'] > $after) {
				$events[] = new TServerEvent(
					$this->formatEventId($state['generation'], $event['sequence']),
					$channel,
					$event['name'],
					$event['data'],
					$event['time']
				);
			}
		}
		return $events;
	}

	/**
	 * Formats an event ID.
	 * @param string $generation The buffer generation.
	 * @param int $sequence The event sequence number.
	 * @return string The event ID.
	 */
	protected function formatEventId(string $generation, int $sequence): string
	{
		return $generation . '-' . $sequence;
	}
}
