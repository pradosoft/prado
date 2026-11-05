<?php

namespace Prado\Test\Unit\Util\Clock;

use Prado\Util\Clock\TClockDecorator;
use Prado\Util\Clock\TMockClock;
use Prado\Util\Clock\TNativeClock;
use Prado\Util\Clock\IClock;
use Psr\Clock\ClockInterface;

class TClockDecoratorTest extends \PHPUnit\Framework\TestCase
{
	public function testImplementsIClock()
	{
		$clock = new TClockDecorator();
		self::assertInstanceOf(IClock::class, $clock);
		self::assertInstanceOf(ClockInterface::class, $clock);
		// A decorator is an IClock that holds one; it is not a source.
		self::assertNotInstanceOf(TNativeClock::class, $clock);
		self::assertNotInstanceOf(TMockClock::class, $clock);
	}

	public function testWrapsTheNativeClockByDefault()
	{
		$clock = new TClockDecorator();
		self::assertInstanceOf(TNativeClock::class, $clock->getClock());
		self::assertSame($clock->getClock(), $clock->getClock()); // memoized
		self::assertEqualsWithDelta(\time(), $clock->now()->getTimestamp(), 2);
		self::assertEqualsWithDelta(\time(), $clock->time(), 2);
		self::assertEqualsWithDelta(\microtime(true), $clock->microtime(), 2.0);
		self::assertSame(date_default_timezone_get(), $clock->timezone()->getName());
	}

	public function testForwardsEveryReadingToTheHeldClock()
	{
		$inner = (new TMockClock())->setNow((new \DateTimeImmutable('@1700000000'))->setTimezone(new \DateTimeZone('Asia/Tokyo')));
		$clock = new TClockDecorator($inner);
		self::assertSame($inner, $clock->getClock());
		self::assertSame(1700000000, $clock->now()->getTimestamp());
		self::assertSame('Asia/Tokyo', $clock->now()->getTimezone()->getName());
		self::assertSame(1700000000, $clock->time());
		self::assertEqualsWithDelta(1700000000.0, $clock->microtime(), 0.0001);
		self::assertSame('Asia/Tokyo', $clock->timezone()->getName());
	}

	public function testSleepForwardsToTheHeldClock()
	{
		// Wrapping a pinned TMockClock: sleep advances the pin instead of blocking.
		$inner = (new TMockClock())->setNow('@1700000000');
		$clock = new TClockDecorator($inner);
		$start = \microtime(true);
		$clock->sleep(30);
		self::assertSame(1700000030, $inner->now()->getTimestamp()); // the held clock advanced
		self::assertSame(1700000030, $clock->now()->getTimestamp()); // visible through the decorator
		self::assertLessThan(1.0, \microtime(true) - $start);        // did not block
	}

	public function testSleepBlocksThroughTheNativeClock()
	{
		$clock = new TClockDecorator();
		$start = \microtime(true);
		$clock->sleep(0.01);
		self::assertGreaterThanOrEqual(0.005, \microtime(true) - $start);
	}

	public function testSetClockReplacesTheHeldClock()
	{
		$clock = new TClockDecorator();
		$inner = (new TMockClock())->setNow('@1700000000');
		self::assertSame($clock, $clock->setClock($inner)); // chaining
		self::assertSame(1700000000, $clock->now()->getTimestamp());
		// Null returns to the default clock class on next use.
		$clock->setClock(null);
		self::assertInstanceOf(TNativeClock::class, $clock->getClock());
		self::assertEqualsWithDelta(\time(), $clock->now()->getTimestamp(), 2);
	}

	public function testGetClockClassIsTheDefaultClockSeam()
	{
		// A subclass changes the clock it wraps by default by overriding getClockClass().
		$clock = new class () extends TClockDecorator {
			protected function getClockClass(): string
			{
				return TMockClock::class;
			}
		};
		self::assertInstanceOf(TMockClock::class, $clock->getClock());
		// An explicitly given clock still wins.
		$given = new TNativeClock();
		$clock = new class ($given) extends TClockDecorator {
			protected function getClockClass(): string
			{
				return TMockClock::class;
			}
		};
		self::assertSame($given, $clock->getClock());
	}

	public function testCreateClockIsTheDefaultClockSeam()
	{
		// A subclass may build the default clock itself by overriding createClock().
		$clock = new class () extends TClockDecorator {
			protected function createClock(): IClock
			{
				return (new TMockClock())->setNow('@1700000000');
			}
		};
		self::assertSame(1700000000, $clock->now()->getTimestamp());
	}

	public function testDecoratorsCompose()
	{
		$inner = (new TMockClock())->setNow('@1700000000');
		$outer = new TClockDecorator(new TClockDecorator($inner));
		self::assertSame(1700000000, $outer->now()->getTimestamp());
		$outer->sleep(5);
		self::assertSame(1700000005, $inner->now()->getTimestamp());
	}
}
