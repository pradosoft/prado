<?php

/**
 * TTestIOEventLog class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\TComponent;

/**
 * TTestIOEventLog records the 'on' events a {@see \Prado\IO\TResource}/{@see \Prado\IO\TStream}
 * (or any {@see TComponent}) raises, so a test can assert lifecycle ordering and payloads.
 *
 * Attach it with {@see listenTo()}, naming the events to watch; the convenience sets
 * {@see RESOURCE_EVENTS} and {@see STREAM_EVENTS} cover the IO layer.  Each fired event is
 * appended as `['event' => name, 'sender' => object, 'param' => value]`, and the accessors
 * report the sequence, counts, and payloads:
 *
 * ```php
 * $log = (new TTestIOEventLog())->listenTo($stream, TTestIOEventLog::STREAM_EVENTS);
 * $stream->seek(2);
 * $stream->close();
 * $log->events();          // ['onSeek', 'onFinalize', 'onClose']
 * $log->countOf('onSeek'); // 1
 * $log->paramsOf('onSeek');// [2]
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestIOEventLog
{
	/** @var array<int, string> The lifecycle events raised by {@see \Prado\IO\TResource}. */
	public const RESOURCE_EVENTS = ['onOpen', 'onFinalize', 'onClose', 'onDetach', 'onError', 'onTimeout', 'onFlush'];

	/** @var array<int, string> The {@see RESOURCE_EVENTS} plus the events {@see \Prado\IO\TStream} adds. */
	public const STREAM_EVENTS = ['onOpen', 'onFinalize', 'onClose', 'onDetach', 'onError', 'onTimeout', 'onFlush', 'onEndOfFile', 'onSeek'];

	/** @var array<int, array{event: string, sender: object, param: mixed}> The recorded events in order. */
	private array $_log = [];

	/**
	 * Attaches a recording handler for each named event on the component.
	 * @param TComponent $component The component to observe.
	 * @param array<int, string> $events The event names to record. Default {@see STREAM_EVENTS}.
	 * @return static This log, for chaining.
	 */
	public function listenTo(TComponent $component, array $events = self::STREAM_EVENTS): static
	{
		foreach ($events as $event) {
			$component->attachEventHandler($event, function ($sender, $param) use ($event): void {
				$this->_log[] = ['event' => $event, 'sender' => $sender, 'param' => $param];
			});
		}
		return $this;
	}

	/**
	 * Returns every recorded event in order.
	 * @return array<int, array{event: string, sender: object, param: mixed}> The records.
	 */
	public function all(): array
	{
		return $this->_log;
	}

	/**
	 * Returns the recorded event names in order.
	 * @return array<int, string> The event names.
	 */
	public function events(): array
	{
		return array_column($this->_log, 'event');
	}

	/**
	 * Counts how many times an event was recorded.
	 * @param string $event The event name.
	 * @return int The number of occurrences.
	 */
	public function countOf(string $event): int
	{
		return count($this->paramsOf($event));
	}

	/**
	 * Indicates whether an event was recorded at least once.
	 * @param string $event The event name.
	 * @return bool Whether it fired.
	 */
	public function has(string $event): bool
	{
		return $this->countOf($event) > 0;
	}

	/**
	 * Returns the parameters passed with each occurrence of an event, in order.
	 * @param string $event The event name.
	 * @return array<int, mixed> The parameters.
	 */
	public function paramsOf(string $event): array
	{
		$params = [];
		foreach ($this->_log as $record) {
			if ($record['event'] === $event) {
				$params[] = $record['param'];
			}
		}
		return $params;
	}

	/**
	 * Returns the parameter of the last occurrence of an event.
	 * @param string $event The event name.
	 * @return mixed The last parameter, or null when the event never fired.
	 */
	public function lastParam(string $event): mixed
	{
		$params = $this->paramsOf($event);
		return $params === [] ? null : end($params);
	}

	/**
	 * Clears the recorded events.
	 */
	public function reset(): void
	{
		$this->_log = [];
	}
}
