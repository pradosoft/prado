<?php

namespace Prado\Test\Unit\Util\Clock;

use Prado\Util\Clock\TNativeClock;
use Prado\Util\Clock\TMockClock;
use Prado\Util\Clock\IClock;
use Psr\Clock\ClockInterface;

class TNativeClockTest extends \PHPUnit\Framework\TestCase
{
	public function testImplementsIClockAndPsrClock()
	{
		$clock = new TNativeClock();
		self::assertInstanceOf(IClock::class, $clock);
		self::assertInstanceOf(ClockInterface::class, $clock);
	}

	public function testCannotBePinnedAndHasNoInnerClock()
	{
		$clock = new TNativeClock();
		// A native clock must never be mistakable for the pinnable TMockClock.
		self::assertNotInstanceOf(TMockClock::class, $clock);
		// It has no setClock()/getClock(): nothing to pin and nothing to route through.
		self::assertFalse(method_exists(TNativeClock::class, 'setClock'));
		self::assertFalse(method_exists(TNativeClock::class, 'getClock'));
		self::assertFalse(method_exists(TNativeClock::class, 'setNow'));
	}

	public function testNowReturnsImmutable()
	{
		self::assertInstanceOf(\DateTimeImmutable::class, (new TNativeClock())->now());
	}

	public function testNowTracksTheSystemClock()
	{
		$before = \time();
		$now = (new TNativeClock())->now()->getTimestamp();
		$after = \time();
		self::assertGreaterThanOrEqual($before, $now);
		self::assertLessThanOrEqual($after, $now);
	}

	public function testNowAdvances()
	{
		$clock = new TNativeClock();
		$first = (float) $clock->now()->format('U.u');
		usleep(2000);
		$second = (float) $clock->now()->format('U.u');
		self::assertGreaterThan($first, $second);
	}

	public function testTimeAndMicrotimeTrackTheSystemClock()
	{
		$clock = new TNativeClock();
		$before = \time();
		$t = $clock->time();
		$after = \time();
		self::assertIsInt($t);
		self::assertGreaterThanOrEqual($before, $t);
		self::assertLessThanOrEqual($after, $t);

		$m = $clock->microtime();
		self::assertIsFloat($m);
		self::assertEqualsWithDelta($t, $m, 2.0);
		self::assertEqualsWithDelta($clock->now()->getTimestamp(), $t, 2);
	}

	public function testTimezoneIsTheSystemTimezone()
	{
		$clock = new TNativeClock();
		self::assertInstanceOf(\DateTimeZone::class, $clock->timezone()); // from IClock via TClockTrait
		self::assertSame(date_default_timezone_get(), $clock->timezone()->getName());
		self::assertSame($clock->timezone()->getName(), $clock->now()->getTimezone()->getName());
	}

	public function testGetSystemTimezoneIsStatic()
	{
		self::assertInstanceOf(\DateTimeZone::class, TNativeClock::getSystemTimezone());
		self::assertSame(date_default_timezone_get(), TNativeClock::getSystemTimezone()->getName());
		// the instance method returns the static source
		self::assertSame(TNativeClock::getSystemTimezone()->getName(), (new TNativeClock())->timezone()->getName());
	}

	public function testNowIsTheTraitBuildInTheSystemZone()
	{
		// TNativeClock adds no now() of its own: the trait's bare build reports in the system zone,
		// which is what timezone() returns, so the two stay consistent without a zone lookup per call.
		self::assertFalse(self::declaresOwnMethod(TNativeClock::class, 'now'));
		$clock = new TNativeClock();
		self::assertSame(date_default_timezone_get(), $clock->now()->getTimezone()->getName());
		self::assertSame($clock->timezone()->getName(), $clock->now()->getTimezone()->getName());
	}

	private static function declaresOwnMethod(string $class, string $method): bool
	{
		$source = file_get_contents((new \ReflectionClass($class))->getFileName());
		return (bool) preg_match('/function\s+' . $method . '\s*\(/', $source);
	}

	public function testNowIsTheOverridableTimeSource()
	{
		// Overriding now() changes the instant; time()/microtime() keep the fast real-time path unless a
		// subclass overrides them too (as TOffsetClock and TMockClock do).
		$clock = new class () extends TNativeClock {
			public function now(): \DateTimeImmutable
			{
				return new \DateTimeImmutable('@1234567890');
			}
		};
		self::assertSame(1234567890, $clock->now()->getTimestamp());
		self::assertEqualsWithDelta(\time(), $clock->time(), 2);
	}

	public function testSleepBlocks()
	{
		$clock = new TNativeClock();
		$start = \microtime(true);
		$clock->sleep(0.01);
		self::assertGreaterThanOrEqual(0.005, \microtime(true) - $start);
	}

	public function testSleepNonPositiveReturnsImmediately()
	{
		$clock = new TNativeClock();
		$start = \microtime(true);
		$clock->sleep(0);
		$clock->sleep(-1);
		self::assertLessThan(0.5, \microtime(true) - $start);
	}

	public function testTraitSleepBlocks()
	{
		// A class that uses TClockTrait directly (not via TNativeClock) blocks with usleep().
		$clock = new class () implements IClock {
			use \Prado\Util\Clock\TClockTrait;
		};
		$start = \microtime(true);
		$clock->sleep(0.01);
		self::assertGreaterThanOrEqual(0.005, \microtime(true) - $start);
	}
}
