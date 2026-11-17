<?php

namespace Prado\Test\Unit\Util\Log;

use Prado\Shell\TShellApplication;
use Prado\TApplication;
use Prado\TApplicationMode;
use Prado\Test\Unit\Harness\Traits\TOutputBufferRestorationTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Log\TBrowserLogRoute;
use Prado\Util\Log\TLogger;

class TBrowserLogRouteTest extends \PHPUnit\Framework\TestCase
{
	use TOutputBufferRestorationTrait;

	protected function setUp(): void
	{
		$this->saveOutputBufferLevel();
	}

	protected function tearDown(): void
	{
		$this->restoreOutputBufferLevel();
	}

	private function render(TBrowserLogRoute $route, array $logs, array $meta = ['maxdelta' => 1.0, 'total' => 2.0]): string
	{
		ob_start();
		try {
			PradoUnit::invoke($route, 'processLogs', $logs, true, $meta);
		} finally {
			$output = ob_get_clean();
		}
		return $output;
	}

	public function testProcessLogsRendersANonStringMessage()
	{
		$route = new TBrowserLogRoute();
		$output = $this->render($route, [TLogEntryFactory::entry(['key' => 'value'])]);
		$this->assertStringContainsString("<td>array\n(\n    'key' =&gt; 'value'\n)</td>", $output);
	}
}
