<?php

namespace Prado\Test\Unit\Util\Clock;

use Prado\Util\Clock\TMonotonicClock;

class TMonotonicClockTest extends \PHPUnit\Framework\TestCase
{
	public function testNanosecondsIsMonotonic()
	{
		$clock = new TMonotonicClock();
		$a = $clock->nanoseconds();
		$b = $clock->nanoseconds();
		self::assertIsInt($a);
		self::assertGreaterThanOrEqual($a, $b); // never goes backward
	}

	public function testSecondsMatchesNanoseconds()
	{
		$clock = new TMonotonicClock();
		$ns = $clock->nanoseconds();
		$s = $clock->seconds();
		self::assertIsFloat($s);
		self::assertEqualsWithDelta($ns / 1000000000, $s, 0.05);
	}

	public function testElapsedMeasuresDuration()
	{
		$clock = new TMonotonicClock();
		$start = $clock->nanoseconds();
		usleep(5000); // 5 ms
		$elapsed = $clock->elapsed($start);
		self::assertGreaterThanOrEqual(0.004, $elapsed);
		self::assertLessThan(1.0, $elapsed);
	}

	public function testHrtimeSeamIsOverridable()
	{
		// Driving the seam makes elapsed time deterministic in tests.
		$clock = new class () extends TMonotonicClock {
			public int $fake = 0;
			protected function hrtime(): int
			{
				return $this->fake;
			}
		};
		$clock->fake = 1000000000; // 1.0 s
		self::assertSame(1000000000, $clock->nanoseconds());
		self::assertSame(1.0, $clock->seconds());
		$start = $clock->nanoseconds();
		$clock->fake = 3500000000; // 3.5 s
		self::assertSame(2.5, $clock->elapsed($start));
	}
}
