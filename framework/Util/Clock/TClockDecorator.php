<?php

/**
 * TClockDecorator class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Clock;

use Prado\TComponent;

/**
 * TClockDecorator class.
 *
 * TClockDecorator is an {@see \Prado\Util\Clock\IClock} that holds another clock through
 * {@see \Prado\Util\Clock\TClockAwareTrait} and forwards every reading to it. Each method is a single
 * forward: {@see now}, {@see time}, {@see microtime}, {@see timezone}, and {@see sleep} return what the
 * held {@see getClock clock} returns. A subclass transforms one or more of them, calling `parent` for the
 * held value.
 *
 * The held clock is given to the constructor. When null, {@see getClock} creates the default through the
 * trait's {@see createClock}, which instantiates the class named by {@see getClockClass}
 * ({@see \Prado\Util\Clock\TNativeClock}). A subclass overrides {@see getClockClass} (or
 * {@see createClock}) to change the clock it wraps by default. {@see setClock} replaces the held clock.
 *
 * {@see \Prado\Util\Clock\TOffsetClock} shifts the held instant by seconds.
 * {@see \Prado\Util\Clock\TTimezoneDecorator} expresses the held {@see now} in another zone.
 * Either wraps any `IClock`, so a pinned {@see \Prado\Util\Clock\TMockClock} makes it deterministic.
 * {@see \Prado\Util\Clock\TTimezoneClock} is a source that reports a zone with the true timestamp.
 *
 * ```php
 * $clock = new TClockDecorator();            // wraps a TNativeClock
 * $clock->now();                             // the native clock's now()
 * $clock = new TClockDecorator($mock);       // wraps a pinned TMockClock
 * $clock->now();                             // the pinned instant
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TClockDecorator extends TComponent implements IClock
{
	use TClockAwareTrait;

	/**
	 * @param ?IClock $clock the clock to wrap; null wraps the {@see getClockClass default} clock
	 */
	public function __construct(?IClock $clock = null)
	{
		parent::__construct();
		$this->setClock($clock);
	}

	// =========================================================================
	// ClockInterface
	// =========================================================================

	/**
	 * @return \DateTimeImmutable the held clock's current moment
	 */
	public function now(): \DateTimeImmutable
	{
		return $this->getClock()->now();
	}

	// =========================================================================
	// IClock
	// =========================================================================

	/**
	 * @return int the held clock's Unix timestamp in whole seconds
	 */
	public function time(): int
	{
		return $this->getClock()->time();
	}

	/**
	 * @return float the held clock's Unix timestamp with microsecond precision
	 */
	public function microtime(): float
	{
		return $this->getClock()->microtime();
	}

	/**
	 * @return \DateTimeZone the zone the held clock reports in
	 */
	public function timezone(): \DateTimeZone
	{
		return $this->getClock()->timezone();
	}

	/**
	 * Pauses through the held clock, so a pinned clock advances instead of blocking.
	 * @param float|int $seconds the number of seconds to pause
	 */
	public function sleep(float|int $seconds): void
	{
		$this->getClock()->sleep($seconds);
	}
}
