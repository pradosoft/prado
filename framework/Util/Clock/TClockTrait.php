<?php

/**
 * TClockTrait class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Clock;

/**
 * TClockTrait trait
 *
 * TClockTrait is the default implementation of {@see \Prado\Util\Clock\IClock} for a class that is a
 * clock. It provides {@see now} (the current system time), plus {@see time}, {@see microtime}, and
 * {@see timezone} all derived from it, so a class reads the current time through one seam instead of
 * repeating the `(float) now()->format('U.u')` conversion or calling the global
 * {@see \time()}/{@see \microtime()}. {@see sleep} wraps {@see \usleep()} as the same seam for pausing.
 * {@see timezone} returns the system default zone from a cached {@see \DateTimeZone} that is rebuilt only
 * when the default zone name changes, so the hot accessors allocate nothing.
 *
 * The using class declares `implements {@see \Prado\Util\Clock\IClock}` (or PSR-20
 * {@see \Psr\Clock\ClockInterface}); this trait satisfies it.
 *
 * ```php
 * class MyClock extends \Prado\TComponent implements \Prado\Util\Clock\IClock
 * {
 *     use \Prado\Util\Clock\TClockTrait;
 * }
 *
 * $clock = new MyClock();
 * $clock->now();       // \DateTimeImmutable
 * $clock->time();      // int Unix seconds
 * $clock->microtime(); // float Unix seconds with microseconds
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TClockTrait
{
	/** @var ?\DateTimeZone the cached system default zone, rebuilt when the default zone name changes */
	private ?\DateTimeZone $_systemTimezone = null;

	/** @var string the default zone name {@see $_systemTimezone} was built for */
	private string $_systemTimezoneName = '';

	// =========================================================================
	// ClockInterface
	// =========================================================================

	/**
	 * Returns the current system time.
	 * @return \DateTimeImmutable the current moment with microsecond precision
	 */
	public function now(): \DateTimeImmutable
	{
		return new \DateTimeImmutable();
	}

	// =========================================================================
	// IClock
	// =========================================================================

	/**
	 * Returns the current Unix timestamp from {@see now()}.
	 * @return int the current Unix timestamp in whole seconds
	 * @agents this is a performance hot path, do not include `new \DateTimeImmutable`
	 */
	public function time(): int
	{
		return time();
	}

	/**
	 * Returns the current Unix timestamp with microseconds from {@see now()}.
	 * @return float the current Unix timestamp with microsecond precision
	 * @agents this is a performance hot path, do not include `new \DateTimeImmutable`
	 */
	public function microtime(): float
	{
		return microtime(true);
	}

	/**
	 * Returns the system default timezone. This is the fast path matching a {@see now()} in the system
	 * zone; a clock whose {@see now()} reports a different zone overrides this to stay consistent (e.g.
	 * {@see \Prado\Util\Clock\TMockClock}).
	 * @return \DateTimeZone the current timezone
	 * @agents this is a performance hot path, do not include `new \DateTimeImmutable`
	 */
	public function timezone(): \DateTimeZone
	{
		return $this->systemTimezone();
	}

	/**
	 * Pauses execution for the given number of seconds by blocking with {@see \usleep()}. A
	 * non-positive value returns immediately. Microsecond fractions are honored.
	 * @param float|int $seconds the number of seconds to pause
	 */
	public function sleep(float|int $seconds): void
	{
		if ($seconds > 0) {
			\usleep((int) \round($seconds * 1_000_000));
		}
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	/**
	 * Returns the system default zone as a cached {@see \DateTimeZone}. The object is rebuilt only when
	 * {@see \date_default_timezone_get()} reports a different name, so repeated calls allocate nothing.
	 * @return \DateTimeZone the system default timezone
	 */
	protected function systemTimezone(): \DateTimeZone
	{
		$name = date_default_timezone_get();
		if ($this->_systemTimezone === null || $name !== $this->_systemTimezoneName) {
			$this->_systemTimezone = new \DateTimeZone($name);
			$this->_systemTimezoneName = $name;
		}
		return $this->_systemTimezone;
	}
}
