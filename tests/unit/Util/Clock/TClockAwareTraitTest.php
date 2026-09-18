<?php

namespace Prado\Test\Unit\Util\Clock;

use Prado\Util\Clock\TClockAwareTrait;
use Prado\Util\Clock\TMockClock;
use Prado\Util\Clock\TNativeClock;
use Prado\Util\Clock\IClock;

class TClockAwareTraitHost
{
	use TClockAwareTrait;
}

class TClockAwareTraitClockHost
{
	use TClockAwareTrait;

	protected function getClockClass(): string
	{
		return TMockClock::class;
	}
}

class TClockAwareTraitBadHost
{
	use TClockAwareTrait;

	protected function getClockClass(): string
	{
		return \stdClass::class; // not an IClock
	}
}

class TClockAwareTraitTest extends \PHPUnit\Framework\TestCase
{
	public function testGetClockLazilyCreatesSystemClockByDefault()
	{
		$host = new TClockAwareTraitHost();
		$clock = $host->getClock();
		// The default is the non-settable system clock, so a settable clock is a deliberate choice.
		self::assertInstanceOf(TNativeClock::class, $clock);
		self::assertInstanceOf(IClock::class, $clock);
		// memoized — same instance on subsequent calls
		self::assertSame($clock, $host->getClock());
	}

	public function testSetClockOverridesTheDependency()
	{
		$host = new TClockAwareTraitHost();
		$injected = new TMockClock();
		$host->setClock($injected);
		self::assertSame($injected, $host->getClock());
	}

	public function testSetClockNullRegeneratesDefault()
	{
		$host = new TClockAwareTraitHost();
		$first = $host->getClock();
		$host->setClock(null);
		$second = $host->getClock();
		self::assertInstanceOf(TNativeClock::class, $second);
		self::assertNotSame($first, $second);
	}

	public function testGetClockClassOverrideChangesTheDefault()
	{
		$host = new TClockAwareTraitClockHost();
		self::assertInstanceOf(TMockClock::class, $host->getClock());
	}

	public function testInvalidClockClassThrows()
	{
		$host = new TClockAwareTraitBadHost();
		self::expectException(\Prado\Exceptions\TConfigurationException::class);
		$host->getClock();
	}

	public function testSetClockReturnsSelfForChaining()
	{
		$host = new TClockAwareTraitHost();
		self::assertSame($host, $host->setClock(new TMockClock()));
		self::assertSame($host, $host->setClock(null));
	}

	public function testCreatedClockIsUsable()
	{
		$host = new TClockAwareTraitHost();
		self::assertInstanceOf(\DateTimeImmutable::class, $host->getClock()->now());
	}
}
