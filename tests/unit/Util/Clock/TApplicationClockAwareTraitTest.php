<?php

namespace Prado\Test\Unit\Util\Clock;

use Prado\Prado;
use Prado\TApplication;
use Prado\Util\Clock\IClock;
use Prado\Util\Clock\TApplicationClockAwareTrait;
use Prado\Util\Clock\TMockClock;
use Prado\Util\Clock\TNativeClock;

class TApplicationClockAwareTraitHost
{
	use TApplicationClockAwareTrait;
}

class TApplicationClockAwareTraitTest extends \PHPUnit\Framework\TestCase
{
	private ?TApplication $_app = null;
	private ?IClock $_appClock = null;

	protected function setUp(): void
	{
		$this->_app = Prado::getApplication();
		$this->_appClock = $this->_app?->getClock();
	}

	protected function tearDown(): void
	{
		Prado::setApplication($this->_app);
		$this->_app?->setClock($this->_appClock);
	}

	public function testGetClockReadsTheApplicationClockLive()
	{
		$host = new TApplicationClockAwareTraitHost();
		self::assertSame($this->_app->getClock(), $host->getClock());

		$mock = new TMockClock();
		$this->_app->setClock($mock);
		self::assertSame($mock, $host->getClock());
	}

	public function testSetClockOverridesTheApplicationClock()
	{
		$host = new TApplicationClockAwareTraitHost();
		$local = new TMockClock();
		$host->setClock($local);
		self::assertSame($local, $host->getClock());

		$host->setClock(null);
		self::assertSame($this->_app->getClock(), $host->getClock());
	}

	public function testGetClockWithoutApplicationCreatesLocalDefault()
	{
		Prado::setApplication(null);
		$host = new TApplicationClockAwareTraitHost();
		$first = $host->getClock();
		self::assertInstanceOf(TNativeClock::class, $first);
		self::assertSame($first, $host->getClock());
	}

	public function testHolderCreatedBeforeApplicationFollowsTheApplicationClock()
	{
		Prado::setApplication(null);
		$host = new TApplicationClockAwareTraitHost();
		$local = $host->getClock();
		self::assertInstanceOf(TNativeClock::class, $local);

		Prado::setApplication($this->_app);
		$mock = new TMockClock();
		$this->_app->setClock($mock);
		self::assertSame($mock, $host->getClock());
		self::assertNotSame($local, $host->getClock());
	}
}
