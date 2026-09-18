<?php

/**
 * TMonotonicClock class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Clock;

use Prado\TComponent;

/**
 * TMonotonicClock class.
 *
 * TMonotonicClock is a thin reader over the system monotonic timer ({@see \hrtime()}). It measures
 * **elapsed time**, not wall-clock time: its values count from an arbitrary boot-relative origin, always
 * move forward, and are immune to wall-clock changes (NTP steps, DST, manual clock changes). Use it for
 * durations and timeouts; use {@see \Prado\Util\Clock\IClock} clocks for "what time is it".
 *
 * It is separate from the wall-clock {@see \Prado\Util\Clock\IClock} hierarchy on purpose — a monotonic
 * counter has no timezone, no epoch meaning, and only differences between readings are meaningful.
 *
 * ```php
 * $mono = new TMonotonicClock();
 * $start = $mono->nanoseconds();
 * // ... work ...
 * $seconds = $mono->elapsed($start);   // float seconds since $start
 * ```
 *
 * {@see hrtime} is the single overridable seam, so tests can drive elapsed time deterministically.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TMonotonicClock extends TComponent
{
	/**
	 * @return int the current monotonic time in nanoseconds, from an arbitrary origin
	 */
	public function nanoseconds(): int
	{
		return $this->hrtime();
	}

	/**
	 * @return float the current monotonic time in seconds, from an arbitrary origin
	 */
	public function seconds(): float
	{
		return $this->hrtime() / 1000000000;
	}

	/**
	 * Returns the seconds elapsed since an earlier {@see nanoseconds} reading.
	 * @param int $sinceNanoseconds a prior {@see nanoseconds} value to measure from
	 * @return float the elapsed seconds
	 */
	public function elapsed(int $sinceNanoseconds): float
	{
		return ($this->hrtime() - $sinceNanoseconds) / 1000000000;
	}

	/**
	 * Reads the system monotonic timer. The single overridable seam; override to control elapsed time in
	 * tests.
	 * @return int the monotonic time in nanoseconds
	 */
	protected function hrtime(): int
	{
		return \hrtime(true);
	}
}
