<?php

/**
 * TSoapServerTest
 *
 * Unit tests for {@see \Prado\Web\Services\TSoapServer}.
 *
 * Covers the {@see TSoapServer::setWsdlStyle WsdlStyle} property: its default,
 * the values it accepts, the values it refuses, and that a generated WSDL
 * follows it. Covers the fault {@see TSoapServer::faultException()} sends for a
 * provider exception in each application mode.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

namespace Prado\Test\Unit\Web\Services;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TUserException;
use Prado\Prado;
use Prado\TApplicationMode;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Log\TLogger;
use Prado\Web\Services\TSoapServer;
use Prado\Web\Services\TSoapService;

/**
 * Provider reflected on by the generation tests.
 */
class TTestSoapQuoteProvider
{
	/**
	 * Returns a quote.
	 * @param string $symbol the symbol to quote
	 * @return float the quote
	 * @soapmethod
	 */
	public function getQuote($symbol)
	{
		return 0.0;
	}
}

// The generator writes the provider name into the WSDL namespace ('urn:<name>wsdl').
// A namespaced name carries backslashes, which a URI cannot hold, so the tests serve
// the provider under a global alias.
if (!class_exists('TTestSoapQuoteProvider', false)) {
	class_alias(TTestSoapQuoteProvider::class, 'TTestSoapQuoteProvider');
}

/**
 * Testable server: the URI and encoding are fixed, so generation needs no
 * request, and the provider class is resolved without configuration.
 */
class TTestSoapServer extends TSoapServer
{
	public function getUri()
	{
		return 'http://example.com/soap';
	}

	public function getEncoding()
	{
		return 'UTF-8';
	}

	/** Exposes the capability check, so a test can skip where the style is absent. */
	public function hasGeneratorStyle(): bool
	{
		return $this->getGeneratorHasStyle();
	}
}

/**
 * Server whose generator predates the style, as an installed 1.1 would be.
 */
class TTestSoapLegacyServer extends TTestSoapServer
{
	protected function getGeneratorHasStyle()
	{
		return false;
	}
}

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
	/** Global alias of {@see TTestSoapQuoteProvider}; its name is valid in a namespace URI. */
	private const PROVIDER = 'TTestSoapQuoteProvider';

	protected function newServer(): TTestSoapServer
	{
		$server = new TTestSoapServer();
		$server->setProvider(self::PROVIDER);
		return $server;
	}

	public function testWsdlStyleDefaultsToRpc()
	{
		$this->assertSame(TSoapServer::WSDL_STYLE_RPC, $this->newServer()->getWsdlStyle());
		$this->assertSame('rpc', $this->newServer()->getWsdlStyle());
	}

	public function testWsdlStyleAcceptsBothStyles()
	{
		$server = $this->newServer();

		$server->setWsdlStyle(TSoapServer::WSDL_STYLE_DOCUMENT);
		$this->assertSame('document', $server->getWsdlStyle());

		$server->setWsdlStyle(TSoapServer::WSDL_STYLE_RPC);
		$this->assertSame('rpc', $server->getWsdlStyle());
	}

	/**
	 * @dataProvider invalidStyleProvider
	 * @param mixed $style the style to set
	 */
	public function testWsdlStyleRefusesAnythingElse($style)
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->newServer()->setWsdlStyle($style);
	}

	public static function invalidStyleProvider(): array
	{
		return [
			'literal' => ['literal'],
			'encoded' => ['encoded'],
			'wrong case' => ['RPC'],
			'empty' => [''],
		];
	}

	public function testTheDefaultStyleGeneratesRpcAndSoapEncoding()
	{
		$wsdl = $this->newServer()->getWsdl();

		$this->assertStringContainsString('style="rpc"', $wsdl);
		$this->assertStringContainsString('use="encoded"', $wsdl);
	}

	public function testTheDocumentStyleGeneratesDocumentAndLiteral()
	{
		$server = $this->newServer();
		if (!$server->hasGeneratorStyle()) {
			$this->markTestSkipped('the installed prado-wsdlgenerator predates the document style');
		}
		$server->setWsdlStyle(TSoapServer::WSDL_STYLE_DOCUMENT);
		$wsdl = $server->getWsdl();

		$this->assertStringContainsString('style="document"', $wsdl);
		$this->assertStringContainsString('use="literal"', $wsdl);
		$this->assertStringNotContainsString('encodingStyle', $wsdl);
	}

	/**
	 * PHP discards an argument a function does not declare, so a generator that
	 * predates the style would answer rpc without complaint. The style is looked
	 * for rather than assumed, and refused where it is missing.
	 */
	public function testADocumentStyleIsRefusedWhereTheGeneratorLacksIt()
	{
		$server = new TTestSoapLegacyServer();
		$server->setProvider(self::PROVIDER);
		$server->setWsdlStyle(TSoapServer::WSDL_STYLE_DOCUMENT);

		$this->expectException(TConfigurationException::class);
		$server->getWsdl();
	}

	/**
	 * The default style is passed the way it always was, so a generator that
	 * predates the style still serves it.
	 */
	public function testTheDefaultStyleGeneratesWhereTheGeneratorLacksTheStyle()
	{
		$server = new TTestSoapLegacyServer();
		$server->setProvider(self::PROVIDER);

		$this->assertStringContainsString('style="rpc"', $server->getWsdl());
	}

	public function testTheStyleConstantsAreTheDocumentedValues()
	{
		$this->assertSame('rpc', TSoapServer::WSDL_STYLE_RPC);
		$this->assertSame('document', TSoapServer::WSDL_STYLE_DOCUMENT);
	}

	public function testAServedWsdlIgnoresTheStyle()
	{
		$file = tempnam(sys_get_temp_dir(), 'wsdl');
		file_put_contents($file, '<definitions/>');

		try {
			$server = $this->newServer();
			$server->setWsdlUri($file);
			$server->setWsdlStyle(TSoapServer::WSDL_STYLE_DOCUMENT);

			$this->assertSame('<definitions/>', $server->getWsdl());
		} finally {
			unlink($file);
		}
	}

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
		$this->assertSame((new THttpException(500, 'errorhandler_internal_error'))->getMessage(), $faults[0][0]);
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
