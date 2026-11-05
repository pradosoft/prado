<?php

namespace Prado\Test\Unit\Util\Clock;

use Prado\Util\Clock\TUTCClock;
use Prado\Util\Clock\TTimezoneClock;
use Prado\Util\Clock\TNativeClock;
use Prado\Util\Clock\IClock;
use Prado\Exceptions\TInvalidOperationException;

class TUTCClockTest extends \PHPUnit\Framework\TestCase
{
	public function testIsAFixedTimezoneClock()
	{
		$clock = new TUTCClock();
		self::assertInstanceOf(TTimezoneClock::class, $clock);
		self::assertInstanceOf(TNativeClock::class, $clock);
		self::assertInstanceOf(IClock::class, $clock);
	}

	public function testWithTimezoneIsForbidden()
	{
		$clock = new TUTCClock();
		self::expectException(TInvalidOperationException::class);
		$clock->withTimezone('Asia/Tokyo');
	}

	public function testNowIsInUTC()
	{
		$clock = new TUTCClock();
		self::assertSame('UTC', $clock->now()->getTimezone()->getName());
		self::assertSame(0, $clock->now()->getOffset());
		self::assertSame('UTC', $clock->timezone()->getName());
		self::assertSame($clock->timezone(), $clock->timezone()); // a field read
	}

	public function testNowTracksRealTime()
	{
		$clock = new TUTCClock();
		$before = \time();
		$ts = $clock->now()->getTimestamp(); // epoch is timezone-independent
		$after = \time();
		self::assertGreaterThanOrEqual($before, $ts);
		self::assertLessThanOrEqual($after, $ts);
	}

	public function testTimeAndMicrotimeAreTheTrueEpoch()
	{
		$clock = new TUTCClock();
		self::assertEqualsWithDelta(\time(), $clock->time(), 2);
		self::assertEqualsWithDelta(\microtime(true), $clock->microtime(), 2.0);
		self::assertEqualsWithDelta($clock->now()->getTimestamp(), $clock->time(), 2);
		self::assertSame(gmdate('Y-m-d H:i'), $clock->now()->format('Y-m-d H:i'));
	}

	public function testIgnoresTheSystemDefaultZone()
	{
		$saved = date_default_timezone_get();
		try {
			$clock = new TUTCClock();
			date_default_timezone_set('Asia/Tokyo');
			self::assertSame('UTC', $clock->now()->getTimezone()->getName());
			self::assertSame('UTC', $clock->timezone()->getName());
			self::assertEqualsWithDelta(\time(), $clock->time(), 2);
		} finally {
			date_default_timezone_set($saved);
		}
	}
}
