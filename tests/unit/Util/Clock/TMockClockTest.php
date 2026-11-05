<?php

namespace Prado\Test\Unit\Util\Clock;

use Prado\Util\Clock\TMockClock;
use Prado\Util\Clock\TNativeClock;
use Prado\Util\Clock\IClock;
use Psr\Clock\ClockInterface;

class TMockClockTest extends \PHPUnit\Framework\TestCase
{
	public function testImplementsIClock()
	{
		$clock = new TMockClock();
		self::assertInstanceOf(IClock::class, $clock);
		self::assertInstanceOf(ClockInterface::class, $clock);
		// Standalone like Symfony's MockClock: not the real TNativeClock.
		self::assertNotInstanceOf(TNativeClock::class, $clock);
		// The pin is Now, not Clock: setClock() only ever means "hold a clock" (TClockAwareTrait).
		self::assertFalse(method_exists(TMockClock::class, 'setClock'));
	}

	public function testFollowsSystemClockWhenNotPinned()
	{
		$clock = new TMockClock();
		self::assertNull($clock->getNow());
		$before = \time();
		$now = $clock->now()->getTimestamp();
		$after = \time();
		self::assertInstanceOf(\DateTimeImmutable::class, $clock->now());
		self::assertGreaterThanOrEqual($before, $now);
		self::assertLessThanOrEqual($after, $now);
	}

	public function testTimeAndMicrotimeUseFastPathWhenNotPinned()
	{
		$clock = new TMockClock();
		$before = \time();
		$t = $clock->time();
		$after = \time();
		self::assertIsInt($t);
		self::assertGreaterThanOrEqual($before, $t);
		self::assertLessThanOrEqual($after, $t);
		self::assertEqualsWithDelta($t, $clock->microtime(), 2.0);
	}

	public function testPinnedInstantIsReturned()
	{
		$clock = new TMockClock();
		$instant = new \DateTimeImmutable('@1700000000');
		$clock->setNow($instant);
		self::assertSame($instant, $clock->getNow());
		self::assertSame(1700000000, $clock->now()->getTimestamp());
		// time()/microtime() report the pinned instant.
		self::assertSame(1700000000, $clock->time());
		self::assertEqualsWithDelta(1700000000.0, $clock->microtime(), 0.0001);
	}

	public function testSetNowNullReleasesToSystemClock()
	{
		$clock = new TMockClock();
		$clock->setNow(new \DateTimeImmutable('@1700000000'));
		$clock->setNow(null);
		self::assertNull($clock->getNow());
		self::assertEqualsWithDelta(\time(), $clock->now()->getTimestamp(), 2);
		self::assertEqualsWithDelta(\time(), $clock->time(), 2); // fast path again
	}

	public function testSetNowConvertsMutableToImmutable()
	{
		$clock = new TMockClock();
		$clock->setNow(new \DateTime('@1700000000'));
		self::assertInstanceOf(\DateTimeImmutable::class, $clock->getNow());
		self::assertSame(1700000000, $clock->now()->getTimestamp());
	}

	public function testSetNowParsesAString()
	{
		$clock = new TMockClock();
		$clock->setNow('@1700000000'); // a timestamp string
		self::assertInstanceOf(\DateTimeImmutable::class, $clock->getNow());
		self::assertSame(1700000000, $clock->now()->getTimestamp());

		$clock->setNow('2024-01-02 03:04:05 UTC'); // a datetime string
		self::assertSame('2024-01-02 03:04:05', $clock->now()->format('Y-m-d H:i:s'));
	}

	public function testSettersReturnSelfForChaining()
	{
		$clock = new TMockClock();
		self::assertSame($clock, $clock->setNow(new \DateTimeImmutable('@1700000000')));
		self::assertSame($clock, $clock->setNow(null));
		self::assertSame($clock, $clock->setTime(1700000000));
		self::assertSame($clock, $clock->setMicrotime(1700000000.5));
		self::assertSame($clock, $clock->setTimezone('UTC'));
		self::assertSame($clock, $clock->setTimezone(null));
	}

	public function testSetNowAcceptsAnIntOrFloatTimestamp()
	{
		$clock = new TMockClock();
		$clock->setNow(1700000000);                                          // int: whole seconds
		self::assertSame(1700000000, $clock->now()->getTimestamp());
		self::assertSame(1700000000, $clock->time());
		self::assertSame(date_default_timezone_get(), $clock->now()->getTimezone()->getName());
		$clock->setNow(1700000000.25);                                       // float: with microseconds
		self::assertSame(1700000000, $clock->time());
		self::assertEqualsWithDelta(1700000000.25, $clock->microtime(), 0.0001);
		self::assertSame('250000', $clock->now()->format('u'));
		$clock->setTimezone('Asia/Tokyo');
		$clock->setNow(1700000000);                                          // built in the fixed zone
		self::assertSame('Asia/Tokyo', $clock->now()->getTimezone()->getName());
		self::assertSame(1700000000, $clock->now()->getTimestamp());
	}

	public function testSetTimePinsAWholeSecondTimestamp()
	{
		$clock = new TMockClock();
		$clock->setTime(1700000000);
		self::assertSame(1700000000, $clock->now()->getTimestamp());
		self::assertSame(1700000000, $clock->time());
		self::assertEqualsWithDelta(1700000000.0, $clock->microtime(), 0.0001);
		// Expressed in the reported zone (the system default when nothing else is set).
		self::assertSame(date_default_timezone_get(), $clock->now()->getTimezone()->getName());
		$clock->setTime(null);
		self::assertNull($clock->getNow());
	}

	public function testSetMicrotimePinsAFractionalTimestamp()
	{
		$clock = new TMockClock();
		$clock->setMicrotime(1700000000.25);
		self::assertSame(1700000000, $clock->time());                          // floored
		self::assertEqualsWithDelta(1700000000.25, $clock->microtime(), 0.0001); // fraction preserved
		self::assertSame('250000', $clock->now()->format('u'));
		$clock->setMicrotime(null);
		self::assertNull($clock->getNow());
	}

	public function testSetTimezoneFixesTheReportedZoneWhenUnpinned()
	{
		$clock = new TMockClock();
		self::assertNull($clock->getTimezone());
		$clock->setTimezone('Asia/Tokyo');
		self::assertSame('Asia/Tokyo', $clock->getTimezone()->getName());
		self::assertSame('Asia/Tokyo', $clock->timezone()->getName());
		self::assertSame('Asia/Tokyo', $clock->now()->getTimezone()->getName());
		self::assertEqualsWithDelta(\time(), $clock->now()->getTimestamp(), 2); // still real time
		// A zone object is accepted too.
		$clock->setTimezone(new \DateTimeZone('UTC'));
		self::assertSame('UTC', $clock->timezone()->getName());
		// Null clears the fixed zone.
		$clock->setTimezone(null);
		self::assertNull($clock->getTimezone());
		self::assertSame(date_default_timezone_get(), $clock->timezone()->getName());
	}

	public function testSetTimezoneReExpressesAPinnedInstant()
	{
		$clock = new TMockClock();
		$clock->setNow(new \DateTimeImmutable('2024-01-01 00:00:00', new \DateTimeZone('UTC')));
		$clock->setTimezone('Asia/Tokyo');
		self::assertSame(1704067200, $clock->now()->getTimestamp());              // same instant
		self::assertSame('Asia/Tokyo', $clock->now()->getTimezone()->getName());
		self::assertSame('2024-01-01 09:00:00', $clock->now()->format('Y-m-d H:i:s'));
		// Clearing the fixed zone leaves the pin in the zone it already has.
		$clock->setTimezone(null);
		self::assertSame('Asia/Tokyo', $clock->timezone()->getName());
	}

	public function testPinsHonorAFixedZone()
	{
		$clock = (new TMockClock())->setTimezone('Asia/Tokyo');
		// A string without a zone is parsed in the fixed zone.
		$clock->setNow('2024-01-01 09:00:00');
		self::assertSame(1704067200, $clock->now()->getTimestamp());
		self::assertSame('Asia/Tokyo', $clock->now()->getTimezone()->getName());
		// An instant in another zone is re-expressed in the fixed zone.
		$clock->setNow(new \DateTimeImmutable('@1700000000'));
		self::assertSame('Asia/Tokyo', $clock->now()->getTimezone()->getName());
		self::assertSame(1700000000, $clock->now()->getTimestamp());
		// Timestamp pins are built in the fixed zone.
		$clock->setTime(1700000000);
		self::assertSame('Asia/Tokyo', $clock->now()->getTimezone()->getName());
		$clock->setMicrotime(1700000000.5);
		self::assertSame('Asia/Tokyo', $clock->now()->getTimezone()->getName());
	}

	public function testPinnedInstantIsFrozen()
	{
		$clock = new TMockClock();
		$clock->setNow(new \DateTimeImmutable('@1700000000'));
		$first = $clock->now();
		usleep(2000);
		$second = $clock->now();
		// Pinned time does not advance between reads.
		self::assertEquals($first, $second);
		self::assertSame(1700000000, $second->getTimestamp());
	}

	public function testPinnedMicrosecondPrecision()
	{
		$clock = new TMockClock();
		$clock->setNow(\DateTimeImmutable::createFromFormat('U.u', '1700000000.500000'));
		self::assertSame(1700000000, $clock->time());                       // floored to whole seconds
		self::assertEqualsWithDelta(1700000000.5, $clock->microtime(), 0.0001); // microseconds preserved
	}

	public function testSleepAdvancesPinnedInstantWithoutBlocking()
	{
		$clock = new TMockClock();
		$clock->setNow(new \DateTimeImmutable('@1700000000'));
		$start = \microtime(true);
		$clock->sleep(5);
		$elapsed = \microtime(true) - $start;
		self::assertSame(1700000005, $clock->now()->getTimestamp()); // advanced by 5 seconds
		self::assertSame(1700000005, $clock->time());                // time() reflects the advance
		self::assertLessThan(1.0, $elapsed);                         // did not actually block
	}

	public function testSleepAdvancesPinnedInstantFractionally()
	{
		$clock = new TMockClock();
		$clock->setNow(\DateTimeImmutable::createFromFormat('U.u', '1700000000.000000'));
		$clock->sleep(0.25);
		self::assertEqualsWithDelta(1700000000.25, $clock->microtime(), 0.0001);
	}

	public function testSleepNegativeRewindsPinnedInstant()
	{
		$clock = new TMockClock();
		$clock->setNow(new \DateTimeImmutable('@1700000000'));
		$clock->sleep(-10);
		self::assertSame(1699999990, $clock->now()->getTimestamp());
	}

	public function testSleepPreservesPinnedTimezone()
	{
		$clock = new TMockClock();
		$clock->setNow(new \DateTimeImmutable('2024-01-01 00:00:00', new \DateTimeZone('Asia/Tokyo')));
		$clock->sleep(60);
		self::assertSame('Asia/Tokyo', $clock->now()->getTimezone()->getName());
		self::assertSame('2024-01-01 00:01:00', $clock->now()->format('Y-m-d H:i:s'));
	}

	public function testTimezoneMatchesPinnedInstant()
	{
		$clock = new TMockClock();
		// Unpinned: system default zone.
		self::assertSame(date_default_timezone_get(), $clock->timezone()->getName());
		// Pinned to a non-default zone: timezone() follows now().
		$clock->setNow(new \DateTimeImmutable('2024-01-01', new \DateTimeZone('Asia/Tokyo')));
		self::assertSame('Asia/Tokyo', $clock->timezone()->getName());
		self::assertSame($clock->now()->getTimezone()->getName(), $clock->timezone()->getName());
	}

	public function testSleepBlocksWhenNotPinned()
	{
		$clock = new TMockClock();
		self::assertNull($clock->getNow());
		$start = \microtime(true);
		$clock->sleep(0.01); // not pinned, so a real pause
		self::assertGreaterThanOrEqual(0.005, \microtime(true) - $start);
	}
}
