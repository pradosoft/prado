<?php

/**
 * TMonotonicClockAwareTrait class file
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Clock;

/**
 * TMonotonicClockAwareTrait trait
 *
 * TMonotonicClockAwareTrait gives a class a `MonotonicClock` dependency: a {@see TMonotonicClock}
 * read through {@see getMonotonicClock}/{@see setMonotonicClock}. A class measures durations,
 * deadlines, and intervals with it, so a wall-clock change (an NTP step, a manual change, a
 * resumed virtual machine) does not lengthen or shorten them. Wall-clock time, such as a timestamp
 * other processes compare, stays on an {@see IClock} from {@see TClockAwareTrait}.
 *
 * When no clock is set, {@see getMonotonicClock} creates a {@see TMonotonicClock} over the system
 * timer. A test sets a subclass whose `hrtime()` it drives.
 *
 * ```php
 * $deadline = $this->getMonotonicClock()->seconds() + $timeout;
 * while ($this->getMonotonicClock()->seconds() < $deadline) {
 *     // ...
 * }
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TMonotonicClockAwareTrait
{
	/** @var ?TMonotonicClock the elapsed-time source, created on first {@see getMonotonicClock} */
	private ?TMonotonicClock $_monotonicClock = null;

	/**
	 * Returns the monotonic clock, creating a {@see TMonotonicClock} when none is set.
	 * @return TMonotonicClock the elapsed-time source
	 */
	public function getMonotonicClock(): TMonotonicClock
	{
		return $this->_monotonicClock ??= new TMonotonicClock();
	}

	/**
	 * @param ?TMonotonicClock $clock the elapsed-time source, or null to use the system timer again
	 * @return $this for method chaining.
	 */
	public function setMonotonicClock(?TMonotonicClock $clock): static
	{
		$this->_monotonicClock = $clock;
		return $this;
	}
}
