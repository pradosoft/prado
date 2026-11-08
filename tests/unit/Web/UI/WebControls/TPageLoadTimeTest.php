<?php


namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\IO\TTextWriter;
use Prado\Prado;
use Prado\Test\Unit\Harness\Util\Clock\TTestMonotonicClock;
use Prado\Web\UI\WebControls\TPageLoadTime;

class TPageLoadTimeTest extends \PHPUnit\Framework\TestCase
{
	private array $server = [];

	protected function setUp(): void
	{
		$this->server = $_SERVER;
	}

	protected function tearDown(): void
	{
		$_SERVER = $this->server;
	}

	public function testSecondSuffix()
	{
		$pageloadtime = new TPageLoadTime();
		$pageloadtime->setSecondSuffix('t');
		self::assertEquals('t', $pageloadtime->getSecondSuffix());
	}

	public function testRequestStartIsSetAtBootstrap()
	{
		self::assertArrayHasKey('PRADO_REQUEST_HRTIME', $_SERVER);
		self::assertSame((int) $_SERVER['PRADO_REQUEST_HRTIME'], Prado::getRequestStartNanoseconds());
		self::assertLessThanOrEqual(hrtime(true), Prado::getRequestStartNanoseconds(), 'The request started before now.');
	}

	public function testRenderContents()
	{
		$writer = new TTextWriter();
		$pageloadtime = new TPageLoadTime();
		$pageloadtime->setSecondSuffix('test');
		$_SERVER['PRADO_REQUEST_HRTIME'] = hrtime(true) - 1_000_000_000;

		$pageloadtime->renderContents($writer);

		// The time is rounded to 5 decimals, so a render within 5 µs of the start shows "1".
		self::assertMatchesRegularExpression('/^1(\.\d+)?test$/', $writer->flush());
	}

	public function testLoadTimeIsMonotonicAndIgnoresAWallClockStep()
	{
		$_SERVER['PRADO_REQUEST_HRTIME'] = 5_000_000_000_000;
		$clock = new TTestMonotonicClock(5_000_000_000_000);
		$pageloadtime = new TPageLoadTime();
		$pageloadtime->setMonotonicClock($clock);
		$clock->advance(1.25);

		foreach ([-3600.0, 3600.0] as $step) {
			$_SERVER['REQUEST_TIME_FLOAT'] = microtime(true) + $step;
			self::assertSame(1.25, $pageloadtime->getLoadTime(), "A wall-clock step of {$step} s does not change the load time.");
		}

		$writer = new TTextWriter();
		$pageloadtime->setSecondSuffix('s');
		$pageloadtime->renderContents($writer);
		self::assertSame('1.25s', $writer->flush());
	}
}
