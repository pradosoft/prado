<?php

/**
 * TSoapServiceTest
 *
 * Unit tests for {@see \Prado\Web\Services\TSoapService}.
 *
 * Covers configuring a soap server from a PHP application configuration, which
 * the XML configuration has always supported.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

namespace Prado\Test\Unit\Web\Services;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TComponent;
use Prado\Web\Services\TSoapServer;
use Prado\Web\Services\TSoapService;
use Prado\Web\THttpResponse;
use Prado\Web\TMediaType;
use Prado\Xml\TXmlDocument;
use Prado\Test\Unit\Harness\Traits\TNestedPathTrait;
use Prado\Test\Unit\PradoUnit;

/**
 * Provider named by the configurations under test.
 */
class TTestSoapServiceProvider
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

/**
 * Server named by a PHP configuration, to show the class key is honored.
 */
class TTestSoapServiceServer extends TSoapServer
{
}

/**
 * A soap server carrying a nested 'Cfg' property, to observe the order the
 * service applies a server's configured properties in.
 */
class TNestedPathSoapServer extends TSoapServer
{
	use TNestedPathTrait;
}

/**
 * A server that records that it ran and serves a fixed WSDL.
 */
class TTestSoapRecordingServer extends TSoapServer
{
	public static int $runs = 0;

	public function run()
	{
		self::$runs++;
	}

	public function getWsdl()
	{
		return '<definitions/>';
	}
}

/**
 * Records the content type, charset, and body written to it.
 */
class TTestSoapResponse extends THttpResponse
{
	public string $contentType = '';
	public string $charset = '';
	public string $body = '';
	public int $clears = 0;

	public function setContentType($value): void
	{
		$this->contentType = $value;
	}

	public function setCharset($charset): void
	{
		$this->charset = $charset;
	}

	public function write($str): void
	{
		$this->body .= $str;
	}

	public function clear(): void
	{
		$this->clears++;
		$this->body = '';
	}
}

/**
 * A service that writes to a recording response.
 */
class TTestSoapRecordingService extends TSoapService
{
	public TTestSoapResponse $response;

	public function __construct()
	{
		$this->response = new TTestSoapResponse();
		parent::__construct();
	}

	public function getResponse()
	{
		return $this->response;
	}
}

class TSoapServiceTest extends \PHPUnit\Framework\TestCase
{
	private array $applicationSnapshot = [];
	private array $requestSnapshot = [];

	protected function setUp(): void
	{
		$application = \Prado\Prado::getApplication();
		$this->applicationSnapshot = PradoUnit::snapshot($application, ['_configType', '_configFileExt']);
		$this->requestSnapshot = PradoUnit::snapshot($application->getRequest(), ['_serviceParam']);
		$this->configurationType(TApplication::CONFIG_TYPE_PHP);
		Prado::setPathOfAlias('SoapFixtures', __DIR__ . DIRECTORY_SEPARATOR . 'SoapServiceApp');
		TTestSoapRecordingServer::$runs = 0;
	}

	protected function tearDown(): void
	{
		$application = \Prado\Prado::getApplication();
		PradoUnit::restore($application, $this->applicationSnapshot);
		PradoUnit::restore($application->getRequest(), $this->requestSnapshot);
	}

	/**
	 * Sets the application's configuration type and the file extension it implies.
	 * @param string $type the configuration type.
	 */
	private function configurationType(string $type): void
	{
		$application = Prado::getApplication();
		$application->setConfigurationType($type);
		PradoUnit::setProp($application, '_configFileExt', null);
	}

	/**
	 * Initializes a service from an XML configuration for a request.
	 * @param string $xml the service configuration.
	 * @param string $serviceParameter the requested server.
	 * @param ?string $configFile the namespace of the service's configuration file.
	 */
	private function initXml(string $xml, string $serviceParameter, ?string $configFile = null): TTestSoapRecordingService
	{
		$this->configurationType(TApplication::CONFIG_TYPE_XML);
		Prado::getApplication()->getRequest()->setServiceParameter($serviceParameter);
		$dom = new TXmlDocument();
		$dom->loadFromString($xml);
		$service = new TTestSoapRecordingService();
		if ($configFile !== null) {
			$service->setConfigFile($configFile);
		}
		$service->init($dom);
		return $service;
	}

	/**
	 * Builds the server a PHP configuration describes.
	 * @param array $config the configuration to load
	 * @param string $id the server to build
	 */
	protected function buildServer(array $config, string $id): TSoapServer
	{
		$service = new TSoapService();
		$reflection = new \ReflectionClass(TSoapService::class);

		$load = $reflection->getMethod('loadConfig');
		$load->invoke($service, $config);

		$serverID = $reflection->getProperty('_serverID');
		$serverID->setValue($service, $id);

		$create = $reflection->getMethod('createServer');
		return $create->invoke($service);
	}

	/**
	 * A PHP configuration used to store a plain array, which createServer() could
	 * not read, so configuring a soap server in PHP raised an Error.
	 */
	public function testAPhpConfigurationBuildsTheServer()
	{
		$server = $this->buildServer(['soap' => ['stockquote' => ['properties' => [
			'provider' => TTestSoapServiceProvider::class,
			'sessionpersistent' => 'true',
			'wsdlstyle' => 'document',
		]]]], 'stockquote');

		$this->assertInstanceOf(TSoapServer::class, $server);
		$this->assertSame(TTestSoapServiceProvider::class, $server->getProvider());
		$this->assertTrue($server->getSessionPersistent());
		$this->assertSame('document', $server->getWsdlStyle());
	}

	/**
	 * A server's configured properties apply parent path first, so a nested value
	 * survives whatever order the configuration declares them in.
	 */
	public function testAConfigurationAppliesParentPathBeforeNestedPath()
	{
		foreach ([
			['Cfg.Size' => 'child', 'Cfg' => 'value'],
			['Cfg' => 'value', 'Cfg.Size' => 'child'],
		] as $properties) {
			$server = $this->buildServer(['soap' => ['quote' => [
				'class' => TNestedPathSoapServer::class,
				'properties' => ['provider' => TTestSoapServiceProvider::class] + $properties,
			]]], 'quote');

			$this->assertSame('child', $server->getCfg()->getSize(), 'declared: ' . implode(', ', array_keys($properties)));
		}
	}

	public function testAPhpConfigurationHonorsTheServerClass()
	{
		$server = $this->buildServer(['soap' => ['quote' => [
			'class' => TTestSoapServiceServer::class,
			'properties' => ['provider' => TTestSoapServiceProvider::class],
		]]], 'quote');

		$this->assertInstanceOf(TTestSoapServiceServer::class, $server);
	}

	public function testAPhpConfigurationWithoutAServerIsAccepted()
	{
		$service = new TSoapService();
		$load = new \ReflectionMethod(TSoapService::class, 'loadConfig');
		$load->invoke($service, ['other' => []]);

		$servers = new \ReflectionProperty(TSoapService::class, '_servers');
		$this->assertSame([], $servers->getValue($service));
	}

	public function testADuplicatedServerIdIsRefused()
	{
		$this->expectException(\Prado\Exceptions\TConfigurationException::class);

		$service = new TSoapService();
		$load = new \ReflectionMethod(TSoapService::class, 'loadConfig');
		$config = ['soap' => ['quote' => ['properties' => []]]];
		$load->invoke($service, $config);
		$load->invoke($service, $config);
	}

	/**
	 * Each server built for a configuration is of the configured class.
	 */
	public function testCreateServerKeepsTheConfiguredClass()
	{
		$service = new TSoapService();
		PradoUnit::invoke($service, 'loadConfig', ['soap' => ['quote' => [
			'class' => TTestSoapServiceServer::class,
			'properties' => ['provider' => TTestSoapServiceProvider::class],
		]]]);
		PradoUnit::setProp($service, '_serverID', 'quote');

		$this->assertInstanceOf(TTestSoapServiceServer::class, PradoUnit::invoke($service, 'createServer'));
		$this->assertInstanceOf(TTestSoapServiceServer::class, PradoUnit::invoke($service, 'createServer'));
	}

	// ── init() and the request ──────────────────────────────────────────────

	/**
	 * A PHP application reads the servers of a PHP configuration file.
	 */
	public function testInitReadsAPhpConfigurationFile()
	{
		Prado::getApplication()->getRequest()->setServiceParameter('filed');
		$service = new TTestSoapRecordingService();
		$service->setConfigFile('SoapFixtures.soap');
		$service->init(null);
		$this->assertStringEndsWith('soap.php', $service->getConfigFile());
		$server = PradoUnit::invoke($service, 'createServer');
		$this->assertInstanceOf(TTestSoapRecordingServer::class, $server);
		$this->assertSame('ISO-8859-1', $server->getEncoding());
	}

	/**
	 * An XML application without a service element reads its configuration file.
	 */
	public function testInitWithoutAnXmlElementReadsTheConfigurationFile()
	{
		$this->configurationType(TApplication::CONFIG_TYPE_XML);
		Prado::getApplication()->getRequest()->setServiceParameter('filed');
		$service = new TTestSoapRecordingService();
		$service->setConfigFile('SoapFixtures.soap');
		$service->init(null);
		$this->assertSame('filed', $service->getServerID());
	}

	/**
	 * A configuration file that holds no configuration is refused.
	 * @dataProvider unreadableConfigFileProvider
	 * @param string $type the application configuration type.
	 * @param string $file the configuration file's namespace.
	 */
	public function testInitRefusesAConfigurationFileWithoutAConfiguration(string $type, string $file)
	{
		$this->configurationType($type);
		Prado::getApplication()->getRequest()->setServiceParameter('filed');
		$service = new TTestSoapRecordingService();
		$service->setConfigFile($file);
		$this->expectException(TConfigurationException::class);
		$service->init(null);
	}

	public static function unreadableConfigFileProvider(): array
	{
		return [
			'malformed xml' => [TApplication::CONFIG_TYPE_XML, 'SoapFixtures.broken'],
			'php without an array' => [TApplication::CONFIG_TYPE_PHP, 'SoapFixtures.scalar'],
		];
	}

	// ── createServer() ──────────────────────────────────────────────────────

	/**
	 * Builds the server of a PHP configuration for the server 'quote'.
	 * @param array $server the server's configuration.
	 */
	private function quoteServer(array $server): TSoapServer
	{
		return $this->buildServer(['soap' => ['quote' => $server]], 'quote');
	}

	// ── run() ───────────────────────────────────────────────────────────────

}
