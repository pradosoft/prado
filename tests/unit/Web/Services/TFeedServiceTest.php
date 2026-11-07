<?php

/**
 * TFeedServiceTest
 *
 * Unit tests for {@see \Prado\Web\Services\TFeedService}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

namespace Prado\Test\Unit\Web\Services;

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TComponent;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Services\TFeedService;

/**
 * A class that is not a feed content provider.
 */
class TFeedServiceTestNotAFeedProvider extends TComponent
{
}

class TFeedServiceTest extends \PHPUnit\Framework\TestCase
{
	private $_configType;
	private $_serviceParameter;

	protected function setUp(): void
	{
		$app = Prado::getApplication();
		$this->_configType = $app->getConfigurationType();
		$this->_serviceParameter = $app->getRequest()->getServiceParameter();
	}

	protected function tearDown(): void
	{
		$app = Prado::getApplication();
		$app->setConfigurationType($this->_configType);
		$app->getRequest()->setServiceParameter($this->_serviceParameter);
	}

	/**
	 * A PHP configuration skips the service `class` key and values that are not arrays.
	 */
	public function testPhpConfigurationSkipsTheServiceClass()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$service = new TFeedService();
		$service->init(['class' => TFeedService::class, 'a' => ['class' => TFeedServiceTestNotAFeedProvider::class], 'b' => null, 'c' => 5]);
		$this->assertSame(['a'], array_keys(PradoUnit::getProp($service, '_feeds')));
	}

	/**
	 * An XML configuration of null registers no feeds.
	 */
	public function testXmlConfigurationNullRegistersNothing()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		$service = new TFeedService();
		$service->init(null);
		$this->assertSame([], PradoUnit::getProp($service, '_feeds'));
	}

	/**
	 * A feed without a class, or with a class that is not a feed provider, throws a feedservice error.
	 */
	public function testRunThrowsFeedServiceErrors()
	{
		try {
			$this->runPhp(['a' => ['properties' => []]], 'a');
			$this->fail('TConfigurationException was not raised.');
		} catch (TConfigurationException $e) {
			$this->assertSame('feedservice_class_required', $e->getErrorCode());
		}
		try {
			$this->runPhp(['a' => ['class' => TFeedServiceTestNotAFeedProvider::class]], 'a');
			$this->fail('TConfigurationException was not raised.');
		} catch (TConfigurationException $e) {
			$this->assertSame('feedservice_feedtype_invalid', $e->getErrorCode());
		}
	}

	/**
	 * Builds a service from a PHP configuration and runs it for the given ID.
	 * @param array $feeds the service configuration.
	 * @param string $id the feed to request.
	 */
	private function runPhp(array $feeds, string $id): void
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		Prado::getApplication()->getRequest()->setServiceParameter($id);
		$service = new TFeedService();
		$service->init($feeds);
		$service->run();
	}
}
