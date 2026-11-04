<?php

/**
 * TTestMonotonicClock class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Util\Clock;

use Prado\Util\Clock\TMonotonicClock;

/**
 * TTestMonotonicClock is a {@see TMonotonicClock} that moves only when a test sets or advances it.
 *
 * {@see $nanoseconds} is the reading of {@see hrtime()}. {@see advance()} adds seconds to it. A
 * test gives a class this clock through `setMonotonicClock()` and then changes the wall clock
 * separately, to show that the class's durations and timeouts follow only this clock.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestMonotonicClock extends TMonotonicClock
{
	/** @var int the monotonic reading, in nanoseconds. */
	public int $nanoseconds = 0;

	/**
	 * @param int $nanoseconds the initial reading, in nanoseconds.
	 */
	public function __construct(int $nanoseconds = 0)
	{
		$this->nanoseconds = $nanoseconds;
		parent::__construct();
	}

	/**
	 * Moves the clock forward.
	 * @param float|int $seconds the seconds to add.
	 * @return static the clock.
	 */
	public function advance(float|int $seconds): static
	{
		$this->nanoseconds += (int) round($seconds * 1_000_000_000);
		return $this;
	}

	/**
	 * Returns the set reading.
	 * @return int the monotonic reading, in nanoseconds.
	 */
	protected function hrtime(): int
	{
		return $this->nanoseconds;
	}
}
