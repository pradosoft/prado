<?php

namespace Prado\Test\Unit\Util\Log;

use Prado\TApplicationMode;
use Prado\Test\Unit\Harness\Traits\TOutputBufferRestorationTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Log\TFirePhpLogRoute;
use Prado\Util\Log\TLogger;

/**
 * A TFirePhpLogRoute whose application and header state can be replaced.
 */
class TAppFirePhpLogRoute extends TFirePhpLogRoute
{
	public mixed $app = null;

	public bool $headersSent = false;

	public function getApplication()
	{
		return $this->app ?? parent::getApplication();
	}

	protected function getHeadersSent(): bool
	{
		return $this->headersSent;
	}
}

/**
 * A FirePHP that records its calls instead of sending headers.
 */
class TRecordingFirePHP extends \FirePHP
{
	public array $calls = [];

	public function setOptions($options)
	{
		$this->calls[] = ['setOptions', $options];
	}

	public function group($name, $options = null)
	{
		$this->calls[] = ['group', $name, $options];
		return true;
	}

	public function groupEnd()
	{
		$this->calls[] = ['groupEnd'];
		return true;
	}

	public function log($object, $label = null, $options = [])
	{
		$this->calls[] = ['log', $object, $label];
		return true;
	}

	public function fb($object)
	{
		$this->calls[] = array_merge(['fb'], func_get_args());
		return true;
	}
}

class TFirePhpLogRouteTest extends \PHPUnit\Framework\TestCase
{
	use TOutputBufferRestorationTrait;

	private $firePhp;

	protected function setUp(): void
	{
		$this->saveOutputBufferLevel();
		$this->firePhp = \FirePHP::getInstance();
	}

	protected function tearDown(): void
	{
		\FirePHP::setInstance($this->firePhp);
		$this->restoreOutputBufferLevel();
	}

	private function render(TAppFirePhpLogRoute $route, array $logs): string
	{
		ob_start();
		try {
			PradoUnit::invoke($route, 'processLogs', $logs, true, ['maxdelta' => 1.0, 'total' => 2.5]);
		} finally {
			$output = ob_get_clean();
			$route->app = null;
		}
		return $output;
	}

	public function testProcessLogsSendsToFirePhp()
	{
		\FirePHP::setInstance($firePhp = new TRecordingFirePHP());
		$route = new TAppFirePhpLogRoute();
		$output = $this->render($route, [
			TLogEntryFactory::entry('first (line 7)', TLogger::ERROR, 'Cat\A', 0.25),
			TLogEntryFactory::entry(['k' => 1], TLogger::INFO, 'Cat\B', 0.5),
		]);

		$this->assertSame('', $output);
		$this->assertSame([
			['setOptions', ['useNativeJsonEncode' => false]],
			['group', TFirePhpLogRoute::DEFAULT_LABEL, ['Collapsed' => true]],
			['log', 'Time,  Message', null],
			['fb', '+0.250000: first ', 'Cat\A', \FirePHP::ERROR],
			['fb', "+0.500000: array\n(\n    'k' => 1\n)", 'Cat\B', \FirePHP::INFO],
			['log', '2.500000', 'Cumulated Time'],
			['groupEnd'],
		], $firePhp->calls);
	}
}
