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
use Prado\Web\THttpRequest;

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

/**
 * Stands in for the \SoapServer engine, whose fault() ends the script, and
 * records each call made to it.
 */
class TTestSoapEngine
{
	/** @var array[] the calls, each [method, ...arguments]. */
	public array $calls = [];

	/** @var ?\Exception the exception handle() throws. */
	public ?\Exception $throw = null;

	public function setClass(...$arguments)
	{
		$this->calls[] = ['setClass', ...$arguments];
	}

	public function setPersistence($mode)
	{
		$this->calls[] = ['setPersistence', $mode];
	}

	public function handle()
	{
		$this->calls[] = ['handle'];
		if ($this->throw !== null) {
			throw $this->throw;
		}
	}

	public function fault(...$arguments)
	{
		$this->calls[] = ['fault', ...$arguments];
	}
}

/**
 * Exposes the server's protected methods.
 */
class TTestSoapOptionsServer extends TSoapServer
{
	public function options(): array
	{
		return $this->getOptions();
	}

	public function engine()
	{
		return $this->createServer();
	}
}

/**
 * A cache held in memory.
 */
class TTestSoapCache implements \Prado\Caching\ICache
{
	public array $items = [];

	public function get($id)
	{
		return $this->items[$id] ?? false;
	}
	public function set($id, $value, $expire = 0, $dependency = null)
	{
		$this->items[$id] = $value;
		return true;
	}
	public function add($id, $value, $expire = 0, $dependency = null)
	{
		return $this->set($id, $value);
	}
	public function delete($id)
	{
		unset($this->items[$id]);
		return true;
	}
	public function flush()
	{
		$this->items = [];
		return true;
	}
	public static function getIsAvailable(): bool
	{
		return true;
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

	// ── ID ──────────────────────────────────────────────────────────────────

	/**
	 * An ID ending in '.wsdl' names the WSDL request of a server, so it is refused.
	 */
	public function testSetIdRefusesAnIdEndingInWsdl()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TSoapServer())->setID('quote.wsdl');
	}

	/**
	 * A server's ID changes from one valid ID to another.
	 */
	public function testSetIdReplacesAValidId()
	{
		$server = new TSoapServer();
		$this->assertNull($server->getID());
		$server->setID('quote');
		$server->setID('wsdl');
		$this->assertSame('wsdl', $server->getID());
		$server->setID(null);
		$this->assertNull($server->getID());
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

	// ── guessMethodCallRequested() ──────────────────────────────────────────

	public function testGuessMethodCallRequestedReadsTheRawBody()
	{
		$request = Prado::getApplication()->getRequest();
		$saved = PradoUnit::getProp($request, '_rawBody');
		PradoUnit::setProp($request, '_rawBody', '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="urn:' . self::PROVIDER . 'wsdl"><soap:Body><ns1:getQuote><symbol>PRADO</symbol></ns1:getQuote></soap:Body></soap:Envelope>');
		try {
			$server = new TTestSoapFaultServer();
			PradoUnit::invoke($server, 'guessMethodCallRequested', self::PROVIDER);
			$this->assertSame('getQuote', $server->getRequestedMethod());
			PradoUnit::setProp($request, '_rawBody', '<soap:Envelope><soap:Body><other:call /></soap:Body></soap:Envelope>');
			$server = new TTestSoapFaultServer();
			PradoUnit::invoke($server, 'guessMethodCallRequested', self::PROVIDER);
			$this->assertNull($server->getRequestedMethod());
		} finally {
			PradoUnit::setProp($request, '_rawBody', $saved);
		}
	}

	// ── run() and fault() ───────────────────────────────────────────────────

	/**
	 * Returns a server that runs on a recording engine.
	 * @param ?string $provider the provider, null for none.
	 */
	private function engineServer(?string $provider): array
	{
		$server = new TTestSoapFaultServer();
		$server->setProvider($provider);
		$engine = new TTestSoapEngine();
		PradoUnit::setProp($server, '_server', $engine);
		return [$server, $engine];
	}

	public function testRunServesTheProviderClass()
	{
		[$server, $engine] = $this->engineServer(self::PROVIDER);
		$server->run();
		$this->assertSame([['setClass', self::PROVIDER, $server], ['handle']], $engine->calls);
		$this->assertNull($server->getRequestedMethod());
	}

	/**
	 * @requires extension soap
	 */
	public function testRunKeepsTheProviderInTheSession()
	{
		[$server, $engine] = $this->engineServer(self::PROVIDER);
		$server->setSessionPersistent(true);
		$server->run();
		$this->assertSame([['setClass', self::PROVIDER, $server], ['setPersistence', SOAP_PERSISTENCE_SESSION], ['handle']], $engine->calls);
	}

	public function testRunWithoutAProviderHandlesTheRequest()
	{
		[$server, $engine] = $this->engineServer(null);
		$server->run();
		$this->assertSame([['handle']], $engine->calls);
	}

	public function testRunRefusesAnUnknownProvider()
	{
		[$server] = $this->engineServer('TTestSoapNoSuchProvider');
		$this->expectException(TConfigurationException::class);
		$server->run();
	}

	public function testRunFaultsAnExceptionOfTheProvider()
	{
		[$server, $engine] = $this->engineServer(null);
		$engine->throw = new TUserException('The quote service is closed.');
		$app = Prado::getApplication();
		$mode = $app->getMode();
		$app->setMode(TApplicationMode::Normal);
		try {
			$server->run();
		} finally {
			$app->setMode($mode);
		}
		$this->assertSame([[$engine->throw->getMessage(), '']], $server->faults);
	}

	public function testFaultSendsTheFaultThroughTheEngine()
	{
		$server = new TSoapServer();
		$engine = new TTestSoapEngine();
		PradoUnit::setProp($server, '_server', $engine);
		$server->fault('Closed', 'details', 'CLIENT', 'actor', 'name');
		$server->fault('Closed');
		$this->assertSame([
			['fault', 'CLIENT', 'Closed', 'actor', 'details', 'name'],
			['fault', 'SERVER', 'Closed', '', '', ''],
		], $engine->calls);
	}

	// ── createServer() and getOptions() ─────────────────────────────────────

	/**
	 * The engine serves a WSDL file and is created once. In Debug mode the WSDL
	 * cache is off, so a changed provider is described again.
	 * @dataProvider modeCacheProvider
	 * @param string $mode the application mode.
	 * @param bool $cacheOff whether the WSDL cache is off after.
	 * @requires extension soap
	 */
	public function testCreateServerServesTheWsdlOnce(string $mode, bool $cacheOff)
	{
		$wsdl = tempnam(sys_get_temp_dir(), 'wsdl');
		file_put_contents($wsdl, $this->newServer()->getWsdl());
		$app = Prado::getApplication();
		$originalMode = $app->getMode();
		$cache = ini_get('soap.wsdl_cache_enabled');
		ini_set('soap.wsdl_cache_enabled', '1');
		$app->setMode($mode);
		try {
			$server = new TTestSoapOptionsServer();
			$server->setWsdlUri($wsdl);
			$engine = $server->engine();
			$this->assertInstanceOf(\SoapServer::class, $engine);
			$this->assertSame($engine, $server->engine());
			$this->assertSame($cacheOff ? '0' : '1', ini_get('soap.wsdl_cache_enabled'));
		} finally {
			$app->setMode($originalMode);
			ini_set('soap.wsdl_cache_enabled', $cache);
			unlink($wsdl);
		}
	}

	public static function modeCacheProvider(): array
	{
		return [
			'debug' => [TApplicationMode::Debug, true],
			'normal' => [TApplicationMode::Normal, false],
		];
	}

	/**
	 * @requires extension soap
	 */
	public function testOptionsCarryTheConfiguredSettings()
	{
		$server = new TTestSoapOptionsServer();
		$this->assertSame([], $server->options());

		$server->setVersion('1.1');
		$server->setActor('urn:actor');
		$server->setEncoding('UTF-8');
		$server->setUri('urn:quote');
		$server->setClassMaps('Quote , Price');
		$this->assertSame([
			'soap_version' => SOAP_1_1,
			'actor' => 'urn:actor',
			'encoding' => 'UTF-8',
			'uri' => 'urn:quote',
			'classmap' => ['Quote' => 'Quote', 'Price' => 'Price'],
		], $server->options());

		$server->setVersion('1.2');
		$this->assertSame(SOAP_1_2, $server->options()['soap_version']);
	}

	// ── getWsdl() ───────────────────────────────────────────────────────────

	public function testGetWsdlRefusesAnUnknownProvider()
	{
		$server = new TTestSoapServer();
		$server->setProvider('TTestSoapNoSuchProvider');
		$this->expectException(TConfigurationException::class);
		$server->getWsdl();
	}

	/**
	 * In Performance mode the WSDL is generated once per style and provider and
	 * then read from the cache.
	 */
	public function testGetWsdlIsCachedInPerformanceMode()
	{
		$app = Prado::getApplication();
		$mode = $app->getMode();
		$cache = $app->getCache();
		$memory = new TTestSoapCache();
		$app->setMode(TApplicationMode::Performance);
		$app->setCache($memory);
		try {
			$wsdl = $this->newServer()->getWsdl();
			$key = TSoapServer::WSDL_CACHE_PREFIX . 'rpc.' . self::PROVIDER;
			$this->assertSame([$key => $wsdl], $memory->items);

			$memory->items[$key] = '<cached/>';
			$this->assertSame('<cached/>', $this->newServer()->getWsdl());
		} finally {
			$app->setMode($mode);
			PradoUnit::setProp($app, '_cache', $cache);
		}
	}

	/**
	 * In Performance mode without a cache the WSDL is generated each time.
	 */
	public function testGetWsdlWithoutACacheInPerformanceMode()
	{
		$app = Prado::getApplication();
		$mode = $app->getMode();
		$cache = $app->getCache();
		$app->setMode(TApplicationMode::Performance);
		PradoUnit::setProp($app, '_cache', null);
		try {
			$this->assertStringContainsString('style="rpc"', $this->newServer()->getWsdl());
		} finally {
			$app->setMode($mode);
			PradoUnit::setProp($app, '_cache', $cache);
		}
	}

	// ── URIs ────────────────────────────────────────────────────────────────

	/**
	 * The URI and WSDL URI default to the running service's URLs for the server.
	 * The request is built from web server variables: the command line script name
	 * is a file path, which is not a URL path on Windows.
	 */
	public function testUrisDefaultToTheServiceUrls()
	{
		$app = Prado::getApplication();
		$running = $app->getService();
		$runningRequest = $app->getRequest();
		$savedServer = $_SERVER;
		$_SERVER['HTTP_HOST'] = 'soap.example.test';
		$_SERVER['SCRIPT_NAME'] = '/index.php';
		$_SERVER['PHP_SELF'] = '/index.php';
		$_SERVER['REQUEST_URI'] = '/index.php?soap=quote';
		unset($_SERVER['HTTPS'], $_SERVER['PATH_INFO'], $_SERVER['ORIG_PATH_INFO'], $_SERVER['ORIG_SCRIPT_NAME']);
		$service = new TSoapService();
		$app->setService($service);
		try {
			(new THttpRequest())->init(null);
			$server = new TSoapServer();
			$server->setID('quote');
			$base = $app->getRequest()->getBaseUrl();
			$this->assertSame('http://soap.example.test', $base);
			$this->assertSame($base . $service->constructUrl('quote', false), $server->getUri());
			$this->assertSame($base . $service->constructUrl('quote.wsdl', false), $server->getWsdlUri());

			$server->setUri('urn:quote');
			$server->setWsdlUri('/quote.wsdl');
			$this->assertSame('urn:quote', $server->getUri());
			$this->assertSame('/quote.wsdl', $server->getWsdlUri());
		} finally {
			$_SERVER = $savedServer;
			$app->setRequest($runningRequest);
			$app->setService($running);
		}
	}

	// ── Properties ──────────────────────────────────────────────────────────

	public function testProperties()
	{
		$server = new TSoapServer();
		$this->assertSame('', $server->getVersion());
		$this->assertSame('', $server->getActor());
		$this->assertSame('', $server->getEncoding());
		$this->assertFalse($server->getSessionPersistent());
		$this->assertNull($server->getClassMaps());
		$this->assertNull($server->getProvider());

		$server->setVersion('1.2');
		$this->assertSame('1.2', $server->getVersion());
		$server->setVersion('');
		$this->assertSame('', $server->getVersion());
		$server->setActor('urn:actor');
		$this->assertSame('urn:actor', $server->getActor());
		$server->setEncoding('UTF-8');
		$this->assertSame('UTF-8', $server->getEncoding());
		$server->setSessionPersistent('true');
		$this->assertTrue($server->getSessionPersistent());
		$server->setClassMaps('Quote');
		$this->assertSame('Quote', $server->getClassMaps());
	}

	public function testVersionRefusesAnUnknownVersion()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TSoapServer())->setVersion('2.0');
	}

	// ── WSDL cache setting ──────────────────────────────────────────────────

	/**
	 * The WSDL cache setting is read as on unless it is '0', and is set, cleared,
	 * and restored to its php.ini value.
	 * @requires extension soap
	 */
	public function testTheWsdlCacheSetting()
	{
		$original = ini_get('soap.wsdl_cache_enabled');
		try {
			ini_set('soap.wsdl_cache_enabled', '1');
			$this->assertTrue(TSoapServer::getIniWsdlCache());
			$this->assertSame('1', TSoapServer::setIniWsdlCache(false));
			$this->assertFalse(TSoapServer::getIniWsdlCache());
			$this->assertSame('0', TSoapServer::setIniWsdlCache(true));
			$this->assertTrue(TSoapServer::getIniWsdlCache());
			ini_set('soap.wsdl_cache_enabled', '');
			$this->assertTrue(TSoapServer::getIniWsdlCache());

			$this->assertTrue(TSoapServer::setIniWsdlCache(null));
			$this->assertSame(get_cfg_var('soap.wsdl_cache_enabled') === false ? '1' : (string) get_cfg_var('soap.wsdl_cache_enabled'), ini_get('soap.wsdl_cache_enabled'));
		} finally {
			ini_set('soap.wsdl_cache_enabled', $original);
		}
	}

	// ── guessMethodCallRequested() ──────────────────────────────────────────

	/**
	 * A body in the provider's namespace without a call in it names no method.
	 */
	public function testGuessMethodCallRequestedWithoutACall()
	{
		$request = Prado::getApplication()->getRequest();
		$saved = PradoUnit::getProp($request, '_rawBody');
		PradoUnit::setProp($request, '_rawBody', '<soap:Envelope xmlns:ns1="urn:' . self::PROVIDER . 'wsdl"><soap:Body /></soap:Envelope>');
		try {
			$server = new TTestSoapFaultServer();
			PradoUnit::invoke($server, 'guessMethodCallRequested', self::PROVIDER);
			$this->assertNull($server->getRequestedMethod());
		} finally {
			PradoUnit::setProp($request, '_rawBody', $saved);
		}
	}
}
