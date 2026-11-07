<?php

/**
 * TJsonServiceTest
 *
 * Unit tests for {@see \Prado\Web\Services\TJsonService}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 */

namespace Prado\Test\Unit\Web\Services;

use Prado\Prado;
use Prado\TApplication;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Services\TJsonService;

class TJsonServiceTest extends \PHPUnit\Framework\TestCase
{
	private $_configType;

	protected function setUp(): void
	{
		$this->_configType = Prado::getApplication()->getConfigurationType();
	}

	protected function tearDown(): void
	{
		Prado::getApplication()->setConfigurationType($this->_configType);
	}

	/**
	 * A PHP configuration registers each `json` entry under its id.
	 */
	public function testPhpConfigurationRegistersJson()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$service = new TJsonService();
		$service->init(['json' => ['a' => ['class' => 'Foo']]]);
		$this->assertArrayHasKey('a', PradoUnit::getProp($service, '_services'));
	}

	/**
	 * A PHP configuration without a `json` key registers nothing.
	 */
	public function testPhpConfigurationWithoutJsonRegistersNothing()
	{
		Prado::getApplication()->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$service = new TJsonService();
		$service->init(['class' => TJsonService::class]);
		$this->assertSame([], PradoUnit::getProp($service, '_services'));
	}
}
