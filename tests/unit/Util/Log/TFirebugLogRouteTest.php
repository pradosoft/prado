<?php

namespace Prado\Test\Unit\Util\Log;

use Prado\Prado;
use Prado\TApplicationMode;
use Prado\Test\Unit\Harness\Traits\TOutputBufferRestorationTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Log\TFirebugLogRoute;
use Prado\Util\Log\TLogger;
use Prado\Web\Services\TPageService;
use Prado\Web\THttpResponse;
use Prado\Web\UI\ActiveControls\TActivePageAdapter;
use Prado\Web\UI\ActiveControls\TCallbackResponseAdapter;

/**
 * A TFirebugLogRoute whose application and service can be replaced.
 */
class TAppFirebugLogRoute extends TFirebugLogRoute
{
	public mixed $app = null;

	public mixed $service = null;

	public function getApplication()
	{
		return $this->app ?? parent::getApplication();
	}

	public function getService()
	{
		return $this->service;
	}
}

/**
 * A TPageService with a fixed requested page.
 */
class TFirebugPageService extends TPageService
{
	public mixed $page = null;

	public function getRequestedPage()
	{
		return $this->page;
	}
}

class TFirebugLogRouteTest extends \PHPUnit\Framework\TestCase
{
	use TOutputBufferRestorationTrait;

	private array $savedApp = [];

	protected function setUp(): void
	{
		$this->saveOutputBufferLevel();
		// TJavaScript::jsonEncode() converts from the Globalization charset; the tests encode UTF-8.
		$this->savedApp = PradoUnit::snapshot(Prado::getApplication(), ['_globalization']);
		PradoUnit::setProp(Prado::getApplication(), '_globalization', null);
	}

	protected function tearDown(): void
	{
		PradoUnit::restore(Prado::getApplication(), $this->savedApp);
		$this->restoreOutputBufferLevel();
	}

	/**
	 * Returns an application stub with a mode and a response.
	 */
	private function app(string $mode = TApplicationMode::Debug, ?THttpResponse $response = null): object
	{
		return new class ($mode, $response ?? new THttpResponse()) {
			public function __construct(public string $mode, public THttpResponse $response)
			{
			}

			public function getMode()
			{
				return $this->mode;
			}

			public function getResponse()
			{
				return $this->response;
			}
		};
	}

	/**
	 * Returns a page service whose requested page is a callback or not; null for no page.
	 */
	private function service(?bool $callback): TFirebugPageService
	{
		$service = new TFirebugPageService();
		if ($callback !== null) {
			$service->page = new class ($callback) {
				public function __construct(public bool $callback)
				{
				}

				public function getIsCallback()
				{
					return $this->callback;
				}
			};
		}
		return $service;
	}

	private function render(TAppFirebugLogRoute $route, array $logs, ?object $app = null, mixed $service = null): string
	{
		$route->app = $app ?? $this->app();
		$route->service = $service;
		ob_start();
		try {
			PradoUnit::invoke($route, 'processLogs', $logs, true, ['maxdelta' => 1.0, 'total' => 2.0]);
		} finally {
			$output = ob_get_clean();
			$route->app = null;
		}
		return $output;
	}

	public function testProcessLogsWritesNothingWithoutLogs()
	{
		$this->assertSame('', $this->render(new TAppFirebugLogRoute(), []));
	}

	public function testProcessLogsWritesNothingInPerformanceMode()
	{
		$output = $this->render(new TAppFirebugLogRoute(), [TLogEntryFactory::entry()], $this->app(TApplicationMode::Performance), $this->service(false));
		$this->assertSame('', $output);
	}

	public function testProcessLogsWritesNothingOutsideAPageRequest()
	{
		$route = new TAppFirebugLogRoute();
		$this->assertSame('', $this->render($route, [TLogEntryFactory::entry()], null, null));
		$this->assertSame('', $this->render($route, [TLogEntryFactory::entry()], null, $this->service(null)));
	}

	public function testProcessLogsWritesAScript()
	{
		$output = $this->render(new TAppFirebugLogRoute(), [
			TLogEntryFactory::entry('first (line 3)', TLogger::WARNING, 'Cat', 0.25, 1.25),
			TLogEntryFactory::entry('second', TLogger::FATAL, 'Cat'),
		], null, $this->service(false));

		$this->assertStringStartsWith('<script', $output);
		$this->assertStringContainsString('var blocks = [["info","Tot Time","Time    ","[Level] [Category] [Message]"],["warn","1.250000","0.250000","[Warning] [Cat] first "],["error"', $output);
		$this->assertStringContainsString('Prado.CallbackRequestManager.logDebug(console, blocks);', $output);
		$this->assertStringContainsString('console.groupEnd();', $output);
		$this->assertStringEndsWith("</script>\n", $output);
	}

	public function testProcessLogsWritesACallbackBlock()
	{
		$response = new THttpResponse();
		$response->setAdapter(new TCallbackResponseAdapter($response));
		$output = $this->render(new TAppFirebugLogRoute(), [TLogEntryFactory::entry('callback')], $this->app(TApplicationMode::Debug, $response), $this->service(true));

		$boundary = TActivePageAdapter::CALLBACK_DEBUG_HEADER;
		$this->assertStringStartsWith('<!--' . $boundary . '-->[["info"', $output);
		$this->assertStringContainsString('"[Info] [Test\\\\Category] callback"', $output);
		$this->assertStringEndsWith('<!--//' . $boundary . '-->', $output);
	}

	public function testProcessLogsEncodesInvalidUtf8AsJson()
	{
		$output = $this->render(new TAppFirebugLogRoute(), [TLogEntryFactory::entry("bad \xB1 utf8")], null, $this->service(false));
		$this->assertStringContainsString('var blocks = [["info","Tot Time","Time    ","[Level] [Category] [Message]"],["info","1.500000","0.500000","[Info] [Test\\\\Category] bad \\ufffd utf8"]];' . "\n", $output);
	}

	public function testFormatFirebugLogMessage()
	{
		$route = new TFirebugLogRoute();
		$this->assertSame('[Debug] [Cat] text', $route->formatFirebugLogMessage(TLogEntryFactory::entry('text', TLogger::DEBUG, 'Cat')));

		$traces = [['file' => '/a.php', 'line' => 3], ['file' => '/b.php', 'line' => 9]];
		$this->assertSame("[Error] [Cat] 42\n    in /a.php:3\n    in /b.php:9", $route->formatFirebugLogMessage(TLogEntryFactory::entry(42, TLogger::ERROR, 'Cat', 0.5, 1.5, $traces)));

		$exception = new \RuntimeException('failed');
		$this->assertStringStartsWith('[Fatal] [Cat] RuntimeException: failed', $route->formatFirebugLogMessage(TLogEntryFactory::entry($exception, TLogger::FATAL, 'Cat')));
	}

	public function testGetFirebugLoggingFunction()
	{
		$route = new TFirebugLogRoute();
		$expected = [
			TLogger::PROFILE => 'info',
			TLogger::PROFILE_BEGIN => 'info',
			TLogger::PROFILE_END => 'info',
			TLogger::DEBUG => 'info',
			TLogger::INFO => 'info',
			TLogger::NOTICE => 'info',
			TLogger::WARNING => 'warn',
			TLogger::ERROR => 'error',
			TLogger::ALERT => 'error',
			TLogger::FATAL => 'error',
			0x80 => 'log',
		];
		foreach ($expected as $level => $function) {
			$this->assertSame($function, PradoUnit::invoke($route, 'getFirebugLoggingFunction', $level), 'level 0x' . dechex($level));
		}
	}

	public function testFormatFirebugLogMessageWithEmptyTraces()
	{
		$route = new TFirebugLogRoute();
		$this->assertSame('[Info] [Cat] text', $route->formatFirebugLogMessage(TLogEntryFactory::entry('text', TLogger::INFO, 'Cat', 0.5, 1.5, [])));
	}
}
