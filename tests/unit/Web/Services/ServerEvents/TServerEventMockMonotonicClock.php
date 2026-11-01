<?php

namespace Prado\Test\Unit\Web\Services\ServerEvents;

use Prado\Util\Clock\TMonotonicClock;

/**
 * A monotonic clock that moves only when a test advances it; TServerEventMockClock::sleep() does.
 */
class TServerEventMockMonotonicClock extends TMonotonicClock
{
	public int $nanoseconds = 0;

	public function advance(float $seconds): void
	{
		$this->nanoseconds += (int) round($seconds * 1000000000);
	}

	protected function hrtime(): int
	{
		return $this->nanoseconds;
	}
}
