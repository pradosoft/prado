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

use Prado\Web\Services\TSoapServer;
use Prado\Web\Services\TSoapService;

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

class TSoapServiceTest extends PHPUnit\Framework\TestCase
{
	private string $configurationType;

	protected function setUp(): void
	{
		$application = Prado\Prado::getApplication();
		$this->configurationType = $application->getConfigurationType();
		$application->setConfigurationType(Prado\TApplication::CONFIG_TYPE_PHP);
	}

	protected function tearDown(): void
	{
		Prado\Prado::getApplication()->setConfigurationType($this->configurationType);
	}

	/**
	 * Builds the server a PHP configuration describes.
	 * @param array $config the configuration to load
	 * @param string $id the server to build
	 */
	protected function buildServer(array $config, string $id): TSoapServer
	{
		$service = new TSoapService();
		$reflection = new ReflectionClass(TSoapService::class);

		$load = $reflection->getMethod('loadConfig');
		$load->setAccessible(true);
		$load->invoke($service, $config);

		$serverID = $reflection->getProperty('_serverID');
		$serverID->setAccessible(true);
		$serverID->setValue($service, $id);

		$create = $reflection->getMethod('createServer');
		$create->setAccessible(true);
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
		$load = new ReflectionMethod(TSoapService::class, 'loadConfig');
		$load->setAccessible(true);
		$load->invoke($service, ['other' => []]);

		$servers = new ReflectionProperty(TSoapService::class, '_servers');
		$servers->setAccessible(true);
		$this->assertSame([], $servers->getValue($service));
	}

	public function testADuplicatedServerIdIsRefused()
	{
		$this->expectException(Prado\Exceptions\TConfigurationException::class);

		$service = new TSoapService();
		$load = new ReflectionMethod(TSoapService::class, 'loadConfig');
		$load->setAccessible(true);
		$config = ['soap' => ['quote' => ['properties' => []]]];
		$load->invoke($service, $config);
		$load->invoke($service, $config);
	}
}
