<?php

namespace Prado\Test\Unit\Util\Log;

use Prado\Shell\TShellApplication;
use Prado\TApplication;
use Prado\TApplicationMode;
use Prado\Test\Unit\Harness\Traits\TOutputBufferRestorationTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Log\TBrowserLogRoute;
use Prado\Util\Log\TLogger;

/**
 * A TBrowserLogRoute whose application can be replaced.
 */
class TAppBrowserLogRoute extends TBrowserLogRoute
{
	public mixed $app = null;

	public function getApplication()
	{
		return $this->app ?? parent::getApplication();
	}
}

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

	public function testProperties()
	{
		$route = new TBrowserLogRoute();
		$this->assertNull($route->getCssClass());
		$this->assertSame($route, $route->setCssClass('log'));
		$this->assertSame('log', $route->getCssClass());

		$this->assertTrue($route->getColorizeDelta());
		$this->assertSame($route, $route->setColorizeDelta('false'));
		$this->assertFalse($route->getColorizeDelta());

		$this->assertFalse($route->getAddPrefix());
		$this->assertSame($route, $route->setAddPrefix('true'));
		$this->assertTrue($route->getAddPrefix());
	}

	public function testProcessLogsWritesNothingWithoutLogs()
	{
		$this->assertSame('', $this->render(new TBrowserLogRoute(), []));
	}

	/**
	 * Renders with a replaced application, restored before the route destructs.
	 */
	private function renderWithApp(object $app, array $logs): string
	{
		$route = new TAppBrowserLogRoute();
		$route->app = $app;
		try {
			return $this->render($route, $logs);
		} finally {
			$route->app = null;
		}
	}

	public function testProcessLogsWritesNothingInPerformanceMode()
	{
		$app = $this->createMock(TApplication::class);
		$app->method('getMode')->willReturn(TApplicationMode::Performance);
		$this->assertSame('', $this->renderWithApp($app, [TLogEntryFactory::entry()]));
	}

	public function testProcessLogsWritesNothingInTheShell()
	{
		$app = $this->createMock(TShellApplication::class);
		$app->method('getMode')->willReturn(TApplicationMode::Debug);
		$this->assertSame('', $this->renderWithApp($app, [TLogEntryFactory::entry()]));
	}

	public function testProcessLogsRendersInlineStyles()
	{
		$route = new TBrowserLogRoute();
		$output = $this->render($route, [
			TLogEntryFactory::entry('slow <b>', TLogger::ERROR, 'Cat\Slow', 0.9),
			TLogEntryFactory::entry('fast (line 12)', TLogger::DEBUG, 'Cat\Fast', 0.1),
			TLogEntryFactory::entry('middle', TLogger::WARNING, 'Cat\Mid', 0.5),
		]);

		$this->assertStringContainsString('<table cellspacing="0"', $output);
		$this->assertStringContainsString('slow &lt;b&gt;', $output);
		$this->assertStringContainsString('<td>fast </td>', $output);
		$this->assertStringContainsString('background-color: #fff;', $output);
		$this->assertStringContainsString('background-color: #e8e8e8;', $output);
		$this->assertStringContainsString('; font-weight: 700', $output);
		$this->assertStringContainsString('; font-weight: 175', $output);
		$this->assertMatchesRegularExpression('/color:#[0-9A-F]{6}">0\.500000</', $output);
		$this->assertStringContainsString('background-color: #ff9933', $output);
		$this->assertStringContainsString('</td></tr></table>', $output);
		$this->assertStringContainsString('>PROFILE BEGIN</span>', $output);
	}

	public function testProcessLogsWithoutColorizeDelta()
	{
		$route = new TBrowserLogRoute();
		$route->setColorizeDelta(false);
		$output = $this->render($route, [TLogEntryFactory::entry('plain', TLogger::INFO, 'Cat', 0.9)]);
		$this->assertStringContainsString('<td style="text-align:center; ">0.900000</td>', $output);
	}

	public function testProcessLogsWithZeroMaxDelta()
	{
		$route = new TBrowserLogRoute();
		$output = $this->render($route, [TLogEntryFactory::entry('zero', TLogger::INFO, 'Cat', 0.0)], ['maxdelta' => 0.0, 'total' => 0.0]);
		$this->assertStringContainsString('; font-weight: 100">0.000000', $output);
	}

	public function testProcessLogsRendersCssClasses()
	{
		$route = new TBrowserLogRoute();
		$route->setCssClass('applog');
		$output = $this->render($route, [
			TLogEntryFactory::entry('one', TLogger::NOTICE, 'Cat\One'),
			TLogEntryFactory::entry('two', TLogger::ALERT, 'Cat\Two'),
		]);
		$this->assertStringContainsString('<table class="applog">', $output);
		$this->assertStringContainsString('<tr class="message level' . TLogger::NOTICE . ' even">', $output);
		$this->assertStringContainsString('<tr class="message level' . TLogger::ALERT . ' odd">', $output);
		$this->assertStringContainsString('<td class="category">Cat\One</td>', $output);
		$this->assertStringContainsString('<tr class="footer"><td colspan="5"><span class="level' . TLogger::DEBUG . '">DEBUG</span>', $output);
	}

	public function testProcessLogsAddsThePrefix()
	{
		$route = new TBrowserLogRoute();
		$route->setAddPrefix(true);
		$output = $this->render($route, [TLogEntryFactory::entry('prefixed', TLogger::INFO, 'Cat')]);
		$this->assertMatchesRegularExpression('/<td>\d{4}-\d\d-\d\d \d\d:\d\d:\d\d \[[^]]*\]\[[^]]*\]\[[^]]*\]\[Info\] \[Cat\] prefixed<\/td>/', $output);
	}

	public function testProcessLogsEncodesTheCategory()
	{
		$route = new TBrowserLogRoute();
		$output = $this->render($route, [TLogEntryFactory::entry('m', TLogger::INFO, '<i>Cat</i>')]);
		$this->assertStringContainsString('<td>&lt;i&gt;Cat&lt;/i&gt;</td>', $output);

		$route->setCssClass('applog');
		$output = $this->render($route, [TLogEntryFactory::entry('m', TLogger::INFO, '<i>Cat</i>')]);
		$this->assertStringContainsString('<td class="category">&lt;i&gt;Cat&lt;/i&gt;</td>', $output);
	}

	public function testProcessLogsRendersANonStringMessage()
	{
		$route = new TBrowserLogRoute();
		$output = $this->render($route, [TLogEntryFactory::entry(['key' => 'value'])]);
		$this->assertStringContainsString("<td>array\n(\n    'key' =&gt; 'value'\n)</td>", $output);
	}

	public function testGetColorLevel()
	{
		$route = new TBrowserLogRoute();
		$expected = [
			TLogger::PROFILE => 'lime',
			TLogger::PROFILE_BEGIN_SELECT => 'lime',
			TLogger::PROFILE_BEGIN => 'lime',
			TLogger::PROFILE_END_SELECT => 'lime',
			TLogger::PROFILE_END => 'lime',
			TLogger::DEBUG => 'green',
			TLogger::INFO => 'black',
			TLogger::NOTICE => '#3333FF',
			TLogger::WARNING => '#33FFFF',
			TLogger::ERROR => '#ff9933',
			TLogger::ALERT => '#ff00ff',
			TLogger::FATAL => 'red',
			0x80 => '',
		];
		foreach ($expected as $level => $color) {
			$this->assertSame($color, PradoUnit::invoke($route, 'getColorLevel', $level), 'level 0x' . dechex($level));
		}
	}
}
