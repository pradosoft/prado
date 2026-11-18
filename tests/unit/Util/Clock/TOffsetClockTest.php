<?php

namespace Prado\Test\Unit\Util\Clock;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Util\Clock\TOffsetClock;
use Prado\Util\Clock\TClockDecorator;
use Prado\Util\Clock\TNativeClock;
use Prado\Util\Clock\TMockClock;
use Prado\Util\Clock\IClock;

class TOffsetClockTest extends \PHPUnit\Framework\TestCase
{
	public function testIsAClockDecorator()
	{
		$clock = new TOffsetClock();
		self::assertInstanceOf(TClockDecorator::class, $clock);
		self::assertInstanceOf(IClock::class, $clock);
		// An offset transforms a held clock; it is not itself the system clock.
		self::assertNotInstanceOf(TNativeClock::class, $clock);
		self::assertInstanceOf(TNativeClock::class, $clock->getClock()); // wraps the native clock by default
	}

	public function testDefaultOffsetIsZeroAndTracksRealTime()
	{
		$clock = new TOffsetClock();
		self::assertSame(0, $clock->getOffset());
		self::assertEqualsWithDelta(\time(), $clock->now()->getTimestamp(), 2);
	}

	public function testPositiveIntegerOffset()
	{
		$clock = new TOffsetClock(90);
		self::assertSame(90, $clock->getOffset());
		self::assertEqualsWithDelta(\time() + 90, $clock->now()->getTimestamp(), 2);
		self::assertEqualsWithDelta($clock->now()->getTimestamp(), $clock->time(), 2);
		self::assertEqualsWithDelta(\microtime(true) + 90, $clock->microtime(), 2.0);
	}

	public function testOffsetAppliedToAHeldClock()
	{
		$base = (new TMockClock())->setNow('@1700000000');

		$ahead = new TOffsetClock(10, $base);
		self::assertSame($base, $ahead->getClock());
		self::assertSame(1700000010, $ahead->now()->getTimestamp());
		self::assertSame(1700000010, $ahead->time());

		$behind = new TOffsetClock(-5, $base);
		self::assertSame(1699999995, $behind->now()->getTimestamp());
		self::assertSame(1699999995, $behind->time());
	}

	public function testFloatOffsetKeepsSubSecondPrecision()
	{
		$clock = new TOffsetClock(1.5, (new TMockClock())->setNow('@1700000000'));
		self::assertSame(1.5, $clock->getOffset());
		self::assertSame(1700000001, $clock->now()->getTimestamp());                 // floor(…1.5)
		self::assertSame('500000', $clock->now()->format('u'));                     // fraction preserved
		self::assertSame(1700000001, $clock->time());
		self::assertEqualsWithDelta(1700000001.5, $clock->microtime(), 0.0001);
	}

	public function testPreservesTheHeldClockTimezone()
	{
		$clock = new TOffsetClock(10);
		self::assertSame(date_default_timezone_get(), $clock->now()->getTimezone()->getName());
		self::assertSame(date_default_timezone_get(), $clock->timezone()->getName());

		$tokyo = (new TMockClock())->setNow((new \DateTimeImmutable('@1700000000'))->setTimezone(new \DateTimeZone('Asia/Tokyo')));
		$clock = new TOffsetClock(10, $tokyo);
		self::assertSame('Asia/Tokyo', $clock->now()->getTimezone()->getName());
		self::assertSame('Asia/Tokyo', $clock->timezone()->getName());
	}

	public function testSleepForwardsToTheHeldClock()
	{
		$base = (new TMockClock())->setNow('@1700000000');
		$clock = new TOffsetClock(10, $base);
		$clock->sleep(5);
		self::assertSame(1700000005, $base->now()->getTimestamp());
		self::assertSame(1700000015, $clock->now()->getTimestamp());
	}

	public function testSetOffsetChangesTheClockInPlace()
	{
		$base = (new TMockClock())->setNow('@1700000000');
		$clock = new TOffsetClock(10, $base);
		self::assertSame($clock, $clock->setOffset(25)); // fluent
		self::assertSame(25, $clock->getOffset());
		self::assertSame(1700000025, $clock->now()->getTimestamp());
		self::assertSame(1700000025, $clock->time());
		self::assertSame($base, $clock->getClock()); // the held clock is untouched
		$clock->setOffset(-0.5);
		self::assertSame(-0.5, $clock->getOffset());
		self::assertSame(1699999999.5, $clock->microtime());
		self::assertSame(1699999999, $clock->time());
		$clock->Offset = 3; // the TComponent property form
		self::assertSame(3, $clock->getOffset());
	}

	public function testFractionalOffsetAcrossTheEpoch()
	{
		$clock = new TOffsetClock(-0.5, (new TMockClock())->setTimezone('UTC')->setTime(0));
		self::assertSame(-0.5, $clock->microtime());
		self::assertSame(-1, $clock->time());
		self::assertSame('1969-12-31 23:59:59.500000', $clock->now()->format('Y-m-d H:i:s.u'));
		self::assertSame(-1, $clock->now()->getTimestamp());
		$clock->setOffset(-1.25);
		self::assertSame('1969-12-31 23:59:58.750000', $clock->now()->format('Y-m-d H:i:s.u'));
	}

	public function testInvalidOffsetThrows()
	{
		foreach ([NAN, INF, -INF] as $offset) {
			try {
				new TOffsetClock($offset);
				self::fail("TOffsetClock accepts the offset $offset.");
			} catch (TInvalidDataValueException $e) {
				self::assertSame('offsetclock_offset_invalid', $e->getErrorCode());
			}
		}
	}

	public function testShift()
	{
		$instant = new \DateTimeImmutable('2024-01-01 00:00:00.999999', new \DateTimeZone('Asia/Tokyo'));
		$shifted = TOffsetClock::shift($instant, 0.000001);
		self::assertSame('2024-01-01 00:00:01.000000', $shifted->format('Y-m-d H:i:s.u'));
		self::assertSame('Asia/Tokyo', $shifted->getTimezone()->getName());
		self::assertSame('2023-12-31 23:59:59.999999', TOffsetClock::shift($instant, -1)->format('Y-m-d H:i:s.u'));
		self::assertSame('2024-01-01 00:00:00.999999', TOffsetClock::shift($instant, 0)->format('Y-m-d H:i:s.u'));
		$mutable = new \DateTime('@1700000000');
		self::assertSame('1700000000.123456', TOffsetClock::shift($mutable, 0.123456)->format('U.u'));
		self::assertSame('1700000000', $mutable->format('U'));
		foreach ([NAN, INF, 1.0e20, PHP_INT_MAX] as $seconds) {
			try {
				TOffsetClock::shift($instant, $seconds);
				self::fail("shift() accepts $seconds seconds.");
			} catch (TInvalidDataValueException $e) {
				self::assertSame('offsetclock_shift_invalid', $e->getErrorCode());
			}
		}
	}
}
