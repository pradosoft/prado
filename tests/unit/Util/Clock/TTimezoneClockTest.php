<?php

namespace Prado\Test\Unit\Util\Clock;

use Prado\Util\Clock\TTimezoneClock;
use Prado\Util\Clock\TNativeClock;
use Prado\Util\Clock\TClockDecorator;
use Prado\Util\Clock\TOffsetClock;
use Prado\Util\Clock\IClock;

class TTimezoneClockTest extends \PHPUnit\Framework\TestCase
{
	public function testIsANativeClock()
	{
		$clock = new TTimezoneClock('UTC');
		self::assertInstanceOf(TNativeClock::class, $clock);
		self::assertInstanceOf(IClock::class, $clock);
		// A source tuned to a zone, not a decorator: nothing is held.
		self::assertNotInstanceOf(TClockDecorator::class, $clock);
		self::assertFalse(method_exists(TTimezoneClock::class, 'getClock'));
	}

	public function testConstructorAcceptsStringOrZone()
	{
		self::assertSame('Asia/Tokyo', (new TTimezoneClock('Asia/Tokyo'))->timezone()->getName());
		self::assertSame('UTC', (new TTimezoneClock(new \DateTimeZone('UTC')))->timezone()->getName());
	}

	public function testDefaultsToSystemTimezone()
	{
		self::assertSame(date_default_timezone_get(), (new TTimezoneClock())->timezone()->getName());
	}

	public function testTimezoneIsAFieldRead()
	{
		$clock = new TTimezoneClock('Asia/Tokyo');
		self::assertSame($clock->timezone(), $clock->timezone()); // the same instance every call
	}

	public function testNowIsInConfiguredTimezone()
	{
		$clock = new TTimezoneClock('Asia/Tokyo');
		self::assertSame('Asia/Tokyo', $clock->now()->getTimezone()->getName());
		self::assertSame(9 * 3600, $clock->now()->getOffset()); // Tokyo is UTC+9, no DST
	}

	public function testNowTracksRealTimeRegardlessOfZone()
	{
		$clock = new TTimezoneClock('Asia/Tokyo');
		$before = \time();
		$ts = $clock->now()->getTimestamp(); // epoch is timezone-independent
		$after = \time();
		self::assertGreaterThanOrEqual($before, $ts);
		self::assertLessThanOrEqual($after, $ts);
	}

	public function testTimeAndMicrotimeAreTheTrueEpoch()
	{
		// A Unix timestamp has no zone: time() is not shifted and equals now()->getTimestamp().
		$clock = new TTimezoneClock('Asia/Tokyo');
		self::assertEqualsWithDelta(\time(), $clock->time(), 2);
		self::assertEqualsWithDelta(\microtime(true), $clock->microtime(), 2.0);
		self::assertEqualsWithDelta($clock->now()->getTimestamp(), $clock->time(), 2);
	}

	public function testTimeIsTheTraitReadNotAnOverride()
	{
		// Only TMockClock overrides the trait's time()/microtime(); a zone clock inherits them.
		$source = file_get_contents((new \ReflectionClass(TTimezoneClock::class))->getFileName());
		self::assertDoesNotMatchRegularExpression('/function\s+time\s*\(/', $source);
		self::assertDoesNotMatchRegularExpression('/function\s+microtime\s*\(/', $source);
	}

	public function testAcceptsAnyPhpTimezoneString()
	{
		// Abbreviations and fixed offsets, not just region names — whatever \DateTimeZone accepts.
		self::assertSame(-8 * 3600, (new TTimezoneClock('PST'))->now()->getOffset());
		self::assertSame(-7 * 3600, (new TTimezoneClock('PDT'))->now()->getOffset());
		self::assertSame(5 * 3600 + 1800, (new TTimezoneClock('+05:30'))->now()->getOffset());
	}

	public function testWithTimezoneReturnsNewInstanceLeavingOriginalUnchanged()
	{
		$tokyo = new TTimezoneClock('Asia/Tokyo');
		$utc = $tokyo->withTimezone('UTC');
		self::assertNotSame($tokyo, $utc);
		self::assertSame('Asia/Tokyo', $tokyo->timezone()->getName()); // original unchanged
		self::assertSame('UTC', $utc->timezone()->getName());
		self::assertSame('UTC', $utc->now()->getTimezone()->getName());
	}

	public function testWithTimezoneAcceptsZoneObject()
	{
		$utc = (new TTimezoneClock('Asia/Tokyo'))->withTimezone(new \DateTimeZone('UTC'));
		self::assertSame('UTC', $utc->timezone()->getName());
	}

	public function testComposesUnderAnOffsetDecorator()
	{
		// The offset decorator wraps the zone source: the instant shifts, the zone is kept.
		$clock = new TOffsetClock(90, new TTimezoneClock('Asia/Tokyo'));
		self::assertSame('Asia/Tokyo', $clock->now()->getTimezone()->getName());
		self::assertSame('Asia/Tokyo', $clock->timezone()->getName());
		self::assertEqualsWithDelta(\time() + 90, $clock->now()->getTimestamp(), 2);
		self::assertEqualsWithDelta(\time() + 90, $clock->time(), 2);
	}
}
