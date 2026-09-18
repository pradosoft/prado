<?php

namespace Prado\Test\Unit\Util\Clock;

use Prado\Util\Clock\TTimezoneDecorator;
use Prado\Util\Clock\TClockDecorator;
use Prado\Util\Clock\TNativeClock;
use Prado\Util\Clock\TMockClock;
use Prado\Util\Clock\TOffsetClock;
use Prado\Util\Clock\IClock;

class TTimezoneDecoratorTest extends \PHPUnit\Framework\TestCase
{
	private static function utc(string $when): int
	{
		return (new \DateTimeImmutable($when, new \DateTimeZone('UTC')))->getTimestamp();
	}

	private static function mockAt(string $when, string $zone = 'UTC'): TMockClock
	{
		return (new TMockClock())->setTimezone($zone)->setTime(self::utc($when));
	}

	public function testIsADecoratorWrappingNativeByDefault()
	{
		$clock = new TTimezoneDecorator('Asia/Tokyo');
		self::assertInstanceOf(TClockDecorator::class, $clock);
		self::assertInstanceOf(IClock::class, $clock);
		self::assertInstanceOf(TNativeClock::class, $clock->getClock());
		self::assertSame('Asia/Tokyo', $clock->timezone()->getName());
		self::assertSame('Asia/Tokyo', $clock->getTimezone()->getName());
		self::assertSame($clock->timezone(), $clock->timezone()); // a field read
	}

	public function testDefaultsToTheSystemZone()
	{
		self::assertSame(date_default_timezone_get(), (new TTimezoneDecorator())->timezone()->getName());
	}

	public function testNowIsTheHeldInstantReExpressedInTheChosenZone()
	{
		$clock = new TTimezoneDecorator('Asia/Tokyo', self::mockAt('2026-01-15 12:00:00'));
		self::assertSame('Asia/Tokyo', $clock->now()->getTimezone()->getName());
		self::assertSame('2026-01-15 21:00:00', $clock->now()->format('Y-m-d H:i:s'));
		self::assertSame(self::utc('2026-01-15 12:00:00'), $clock->now()->getTimestamp()); // the same instant
	}

	public function testTimeAndMicrotimeAreTheHeldEpochUnchanged()
	{
		$mock = self::mockAt('2026-01-15 12:00:00', 'Europe/Paris');
		$clock = new TTimezoneDecorator('Asia/Tokyo', $mock);
		self::assertSame($mock->time(), $clock->time());
		self::assertSame($mock->microtime(), $clock->microtime());
		self::assertSame($clock->now()->getTimestamp(), $clock->time());
	}

	public function testTimeIsTheDecoratorForwardNotAnOverride()
	{
		$source = file_get_contents((new \ReflectionClass(TTimezoneDecorator::class))->getFileName());
		self::assertDoesNotMatchRegularExpression('/function\s+time\s*\(/', $source);
		self::assertDoesNotMatchRegularExpression('/function\s+microtime\s*\(/', $source);
	}

	public function testDstIsHandledByTheZone()
	{
		$mock = self::mockAt('2026-01-15 12:00:00');
		$clock = new TTimezoneDecorator('America/New_York', $mock);
		self::assertSame('07:00 EST', $clock->now()->format('H:i T'));
		$mock->setTime(self::utc('2026-07-15 12:00:00'));
		self::assertSame('08:00 EDT', $clock->now()->format('H:i T'));
	}

	public function testHeldZoneIsIgnoredForNow()
	{
		$mock = self::mockAt('2026-01-15 12:00:00', 'America/Los_Angeles');
		$clock = new TTimezoneDecorator('Europe/London', $mock);
		self::assertSame('Europe/London', $clock->now()->getTimezone()->getName());
		self::assertSame('2026-01-15 12:00:00', $clock->now()->format('Y-m-d H:i:s'));
		self::assertSame('America/Los_Angeles', $mock->now()->getTimezone()->getName()); // held clock untouched
	}

	public function testAcceptsAnyPhpTimezone()
	{
		$mock = self::mockAt('2026-01-15 12:00:00');
		self::assertSame('17:30', (new TTimezoneDecorator('+05:30', $mock))->now()->format('H:i'));
		self::assertSame('04:00', (new TTimezoneDecorator('PST', $mock))->now()->format('H:i'));
		self::assertSame('21:00', (new TTimezoneDecorator(new \DateTimeZone('Asia/Tokyo'), $mock))->now()->format('H:i'));
	}

	public function testSetTimezoneIsAFluentProperty()
	{
		$clock = new TTimezoneDecorator('UTC', self::mockAt('2026-01-15 12:00:00'));
		self::assertSame($clock, $clock->setTimezone(new \DateTimeZone('Asia/Tokyo')));
		self::assertSame('Asia/Tokyo', $clock->timezone()->getName());
		self::assertSame('21:00', $clock->now()->format('H:i'));
		$clock->Timezone = 'Europe/Paris';
		self::assertSame('Europe/Paris', $clock->getTimezone()->getName());
		self::assertSame('13:00', $clock->now()->format('H:i'));
	}

	public function testTracksRealTimeOverTheNativeClock()
	{
		$clock = new TTimezoneDecorator('Asia/Tokyo');
		self::assertEqualsWithDelta(\time(), $clock->time(), 2);
		self::assertEqualsWithDelta(\microtime(true), $clock->microtime(), 2.0);
		self::assertEqualsWithDelta(\time(), $clock->now()->getTimestamp(), 2);
		self::assertSame('Asia/Tokyo', $clock->now()->getTimezone()->getName());
		self::assertSame(9 * 3600, $clock->now()->getOffset());
	}

	public function testSleepForwardsToTheHeldClock()
	{
		$mock = self::mockAt('2026-01-15 12:00:00');
		$clock = new TTimezoneDecorator('Asia/Tokyo', $mock);
		$clock->sleep(5);
		self::assertSame(self::utc('2026-01-15 12:00:05'), $mock->time());
		self::assertSame('21:00:05', $clock->now()->format('H:i:s'));
	}

	public function testComposesWithAnOffsetDecorator()
	{
		$mock = self::mockAt('2026-01-15 12:00:00');
		$clock = new TOffsetClock(90, new TTimezoneDecorator('Asia/Tokyo', $mock));
		self::assertSame('Asia/Tokyo', $clock->timezone()->getName());
		self::assertSame($mock->time() + 90, $clock->time());
		self::assertSame('2026-01-15 21:01:30', $clock->now()->format('Y-m-d H:i:s'));
		$inner = new TTimezoneDecorator('Asia/Tokyo', new TOffsetClock(90, $mock));
		self::assertSame('2026-01-15 21:01:30', $inner->now()->format('Y-m-d H:i:s')); // order does not matter
	}
}
