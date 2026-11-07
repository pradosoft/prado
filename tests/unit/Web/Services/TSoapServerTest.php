<?php

/**
 * TSoapServerTest
 *
 * Unit tests for the SOAP faults of {@see \Prado\Web\Services\TSoapServer}.
 */

namespace Prado\Test\Unit\Web\Services;

use Prado\Exceptions\THttpException;
use Prado\Exceptions\TUserException;
use Prado\Prado;
use Prado\TApplicationMode;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\TLogger;
use Prado\Web\Services\TSoapServer;
use Prado\Web\Services\TSoapService;


/**
 * Server that records its faults, so a test reads what the client would receive.
 */
class TTestSoapFaultServer extends TSoapServer
{
	/** @var array[] the faults sent, each [title, details]. */
	public array $faults = [];

	public function fault($title, $details = '', $code = 'SERVER', $actor = '', $name = '')
	{
		$this->faults[] = [$title, $details];
	}
}

class TSoapServerTest extends \PHPUnit\Framework\TestCase
{
	// ── faultException() ────────────────────────────────────────────────────

	/**
	 * Returns the faults the server sends for the exception in the application mode.
	 */
	private function faultsFor(\Exception $exception, string $mode): array
	{
		$app = Prado::getApplication();
		$originalMode = $app->getMode();
		$app->setMode($mode);
		try {
			$server = new TTestSoapFaultServer();
			PradoUnit::invoke($server, 'faultException', $exception);
			return $server->faults;
		} finally {
			$app->setMode($originalMode);
		}
	}

	private function countErrorLogs(): int
	{
		return count(Prado::getLogger()->getLogs(TLogger::ERROR, TSoapService::class));
	}

	public function testDebugModeFaultsWithTheMessageAndTheTrace()
	{
		$exception = new \RuntimeException('secret: /var/www/db.php');
		$faults = $this->faultsFor($exception, TApplicationMode::Debug);
		$this->assertCount(1, $faults);
		$this->assertSame('secret: /var/www/db.php', $faults[0][0]);
		$this->assertSame($exception->__toString(), $faults[0][1]);
	}

	public function testNormalModeFaultsWithTheGenericSentenceAndLogs()
	{
		$logs = $this->countErrorLogs();
		$faults = $this->faultsFor(new \RuntimeException('secret: /var/www/db.php'), TApplicationMode::Normal);
		$this->assertCount(1, $faults);
		$this->assertSame((new TUserException('soapserver_internal_error'))->getMessage(), $faults[0][0]);
		$this->assertSame('', $faults[0][1]);
		$this->assertStringNotContainsString('secret', $faults[0][0]);
		$this->assertSame($logs + 1, $this->countErrorLogs());
	}

	public function testPerformanceModeHidesTheMessage()
	{
		$faults = $this->faultsFor(new \RuntimeException('secret'), TApplicationMode::Performance);
		$this->assertStringNotContainsString('secret', $faults[0][0]);
	}

	public function testNormalModeFaultsWithAUserExceptionMessage()
	{
		$logs = $this->countErrorLogs();
		$exception = new TUserException('The quote service is closed.');
		$faults = $this->faultsFor($exception, TApplicationMode::Normal);
		$this->assertSame([[$exception->getMessage(), '']], $faults);
		$this->assertSame($logs, $this->countErrorLogs());
	}

	public function testNormalModeFaultsWithAnHttpExceptionMessage()
	{
		$exception = new THttpException(404, 'jsonservice_provider_unknown', 'quote');
		$faults = $this->faultsFor($exception, TApplicationMode::Normal);
		$this->assertSame([[$exception->getMessage(), '']], $faults);
	}
}

