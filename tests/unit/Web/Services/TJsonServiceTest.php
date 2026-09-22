<?php

/**
 * TJsonServiceTest
 *
 * Unit tests for {@see \Prado\Web\Services\TJsonService}.
 *
 * Covers the order the service applies a JSON response's configured properties
 * in. A response returning null content leaves the HTTP response untouched, so
 * the property application is exercised on its own.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

namespace Prado\Test\Unit\Web\Services;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TComponent;
use Prado\Test\Unit\Harness\Traits\TNestedPathTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Services\TJsonResponse;
use Prado\Web\Services\TJsonService;
use Prado\Xml\TXmlDocument;

/**
 * A JSON response carrying a nested 'Cfg' property. Its content is null so the
 * service never reaches the HTTP response.
 */
class TNestedPathJsonResponse extends TJsonResponse
{
	use TNestedPathTrait;

	/** @var ?TNestedPathJsonResponse the response the service built last. */
	public static $last = null;

	/** @var mixed content served; null leaves the HTTP response untouched. */
	public static $content = null;

	/** @var mixed the config init() received. */
	public $initConfig = false;

	public function __construct()
	{
		parent::__construct();
		self::$last = $this;
	}

	public function init($config)
	{
		$this->initConfig = $config;
	}

	public function getJsonContent()
	{
		return self::$content;
	}
}

/**
 * A response class that is not a TJsonResponse, which the service refuses.
 */
class TNotAJsonResponse extends TComponent
{
}

class TJsonServiceTest extends \PHPUnit\Framework\TestCase
{
	private $_configurationType;
	private $_serviceParameter;

	protected function setUp(): void
	{
		$this->_configurationType = Prado::getApplication()->getConfigurationType();
		$this->_serviceParameter = Prado::getApplication()->getRequest()->getServiceParameter();
		TNestedPathJsonResponse::$last = null;
		TNestedPathJsonResponse::$content = null;
	}

	protected function tearDown(): void
	{
		Prado::getApplication()->setConfigurationType($this->_configurationType);
		Prado::getApplication()->getRequest()->setServiceParameter($this->_serviceParameter);
		TNestedPathJsonResponse::$last = null;
		TNestedPathJsonResponse::$content = null;
	}

	/**
	 * Builds a service from a PHP configuration and runs it for the given ID.
	 * @param array $json the `json` service definitions.
	 * @param string $id the service to request.
	 */
	private function runPhp(array $json, string $id): TJsonService
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		Prado::getApplication()->getRequest()->setServiceParameter($id);
		$service = new TJsonService();
		$service->init(['json' => $json]);
		$service->run();
		return $service;
	}

	/**
	 * Builds a service from an XML configuration and runs it for the given ID.
	 * @param string $elements the `<json>` elements as written.
	 * @param string $id the service to request.
	 */
	private function runXml(string $elements, string $id): TJsonService
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		Prado::getApplication()->getRequest()->setServiceParameter($id);
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<service>' . $elements . '</service>');
		$service = new TJsonService();
		$service->init($config);
		$service->run();
		return $service;
	}

	/**
	 * A response's configured properties apply parent path first, so a nested
	 * value survives whatever order the configuration declares them in.
	 */
	public function testCreateJsonResponseAppliesParentPathBeforeNestedPath()
	{
		foreach ([
			['Cfg.Size' => 'child', 'Cfg' => 'value'],
			['Cfg' => 'value', 'Cfg.Size' => 'child'],
		] as $properties) {
			$service = new TJsonService();
			$response = new TNestedPathJsonResponse();
			PradoUnit::invoke($service, 'createJsonResponse', $response, $properties, null);
			$this->assertEquals('child', $response->getCfg()->getSize(), 'declared: ' . implode(', ', array_keys($properties)));
		}
	}

	// -------------------------------------------------------------------------
	// loadJsonServices() — configuration branches
	// -------------------------------------------------------------------------

	/**
	 * A PHP configuration registers each service under its ID.
	 */
	public function testPhpConfigurationRegistersTheServices()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$service = new TJsonService();
		$service->init(['json' => ['a' => ['class' => TNestedPathJsonResponse::class]]]);
		$this->assertArrayHasKey('a', PradoUnit::getProp($service, '_services'));
	}

	/**
	 * An XML configuration registers each `<json>` element under its id attribute.
	 */
	public function testXmlConfigurationRegistersTheServices()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<service><json id="a" class="' . TNestedPathJsonResponse::class . '"/></service>');
		$service = new TJsonService();
		$service->init($config);
		$this->assertArrayHasKey('a', PradoUnit::getProp($service, '_services'));
	}

	/**
	 * A `<json>` element without an id attribute is refused.
	 */
	public function testXmlConfigurationWithoutAnIdIsRefused()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<service><json class="' . TNestedPathJsonResponse::class . '"/></service>');
		$service = new TJsonService();
		$this->expectException(TConfigurationException::class);
		$service->init($config);
	}

	/**
	 * A null configuration registers nothing, under either configuration type.
	 */
	public function testANullConfigurationRegistersNothing()
	{
		foreach ([TApplication::CONFIG_TYPE_PHP, TApplication::CONFIG_TYPE_XML] as $type) {
			Prado::getApplication()->setConfigurationType($type);
			$service = new TJsonService();
			$service->init(null);
			$this->assertSame([], PradoUnit::getProp($service, '_services'));
		}
	}

	// -------------------------------------------------------------------------
	// run() — dispatch
	// -------------------------------------------------------------------------

	/**
	 * A PHP configuration builds the named response and applies its properties.
	 */
	public function testRunFromPhpConfigurationBuildsTheResponse()
	{
		$this->runPhp(['a' => [
			'class' => TNestedPathJsonResponse::class,
			'properties' => ['Cfg' => 'value'],
		]], 'a');

		$this->assertNotNull(TNestedPathJsonResponse::$last);
		$this->assertSame('parent:value', TNestedPathJsonResponse::$last->getCfg()->getSize());
	}

	/**
	 * A PHP service definition without properties is accepted.
	 */
	public function testRunFromPhpConfigurationWithoutPropertiesIsAccepted()
	{
		$this->runPhp(['a' => ['class' => TNestedPathJsonResponse::class]], 'a');
		$this->assertNotNull(TNestedPathJsonResponse::$last);
		$this->assertSame('unset', TNestedPathJsonResponse::$last->getCfg()->getSize());
	}

	/**
	 * A PHP service definition without a class is refused.
	 */
	public function testRunFromPhpConfigurationWithoutAClassIsRefused()
	{
		$this->expectException(TConfigurationException::class);
		$this->runPhp(['a' => ['properties' => []]], 'a');
	}

	/**
	 * A PHP service class that is not a TJsonResponse is refused.
	 */
	public function testRunFromPhpConfigurationWithAnInvalidResponseTypeIsRefused()
	{
		$this->expectException(TConfigurationException::class);
		$this->runPhp(['a' => ['class' => TNotAJsonResponse::class]], 'a');
	}

	/**
	 * An XML configuration builds the named response and applies its attributes.
	 */
	public function testRunFromXmlConfigurationBuildsTheResponse()
	{
		$this->runXml('<json id="a" class="' . TNestedPathJsonResponse::class . '" Cfg="value"/>', 'a');

		$this->assertNotNull(TNestedPathJsonResponse::$last);
		$this->assertSame('parent:value', TNestedPathJsonResponse::$last->getCfg()->getSize());
	}

	/**
	 * An XML service definition without a class attribute is refused.
	 */
	public function testRunFromXmlConfigurationWithoutAClassIsRefused()
	{
		$this->expectException(TConfigurationException::class);
		$this->runXml('<json id="a"/>', 'a');
	}

	/**
	 * An XML service class that is not a TJsonResponse is refused.
	 */
	public function testRunFromXmlConfigurationWithAnInvalidResponseTypeIsRefused()
	{
		$this->expectException(TConfigurationException::class);
		$this->runXml('<json id="a" class="' . TNotAJsonResponse::class . '"/>', 'a');
	}

	/**
	 * An unregistered service ID is a 404.
	 */
	public function testRunWithAnUnknownServiceIdIsNotFound()
	{
		$this->expectException(THttpException::class);
		$this->runPhp(['a' => ['class' => TNestedPathJsonResponse::class]], 'nosuchservice');
	}

	// -------------------------------------------------------------------------
	// createJsonResponse() — content
	// -------------------------------------------------------------------------

	/**
	 * Non-null content is JSON encoded onto the response.
	 */
	public function testCreateJsonResponseWritesNonNullContent()
	{
		TNestedPathJsonResponse::$content = ['a' => 1];
		$obLevel = ob_get_level();
		try {
			$service = new TJsonService();
			$response = new TNestedPathJsonResponse();
			PradoUnit::invoke($service, 'createJsonResponse', $response, [], 'the-config');
			$this->assertSame('the-config', $response->initConfig);
		} finally {
			while (ob_get_level() > $obLevel) {
				ob_end_clean();
			}
		}
	}

	/**
	 * Null content leaves the HTTP response untouched.
	 */
	public function testCreateJsonResponseSkipsNullContent()
	{
		TNestedPathJsonResponse::$content = null;
		$service = new TJsonService();
		$response = new TNestedPathJsonResponse();
		PradoUnit::invoke($service, 'createJsonResponse', $response, [], null);
		$this->assertNull($response->initConfig);
	}
}
