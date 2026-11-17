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

	public function testProcessLogsWritesNothingOutsideAPageRequest()
	{
		$route = new TAppFirebugLogRoute();
		$this->assertSame('', $this->render($route, [TLogEntryFactory::entry()], null, null));
		$this->assertSame('', $this->render($route, [TLogEntryFactory::entry()], null, $this->service(null)));
	}

	public function testProcessLogsEncodesInvalidUtf8AsJson()
	{
		$output = $this->render(new TAppFirebugLogRoute(), [TLogEntryFactory::entry("bad \xB1 utf8")], null, $this->service(false));
		$this->assertStringContainsString('var blocks = [["info","Tot Time","Time    ","[Level] [Category] [Message]"],["info","1.500000","0.500000","[Info] [Test\\\\Category] bad \\ufffd utf8"]];' . "\n", $output);
	}
}
