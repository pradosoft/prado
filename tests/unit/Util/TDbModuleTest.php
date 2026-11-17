<?php

namespace Prado\Test\Unit\Util;

use Prado\Exceptions\TInvalidOperationException;
use Prado\Util\IDbModule;
use Prado\Util\TDbModule;
use Prado\Util\TDbPluginModule;
use Prado\Util\Traits\TInitializedTrait;

class TDbModuleTestInitialized extends TDbModule
{
	use TInitializedTrait;

	public function init($config)
	{
		parent::init($config);
		$this->markInitialized();
	}
}

class TDbPluginModuleTestInitialized extends TDbPluginModule
{
	use TInitializedTrait;

	public function init($config)
	{
		parent::init($config);
		$this->markInitialized();
	}
}

class TDbModuleTest extends \PHPUnit\Framework\TestCase
{
	public static function moduleProvider(): array
	{
		return [
			'TDbModule' => [TDbModule::class, TDbModuleTestInitialized::class],
			'TDbPluginModule' => [TDbPluginModule::class, TDbPluginModuleTestInitialized::class],
		];
	}

	/**
	 * @dataProvider moduleProvider
	 */
	public function testConnectionIdWithoutInitializedTrait(string $class, string $initializedClass)
	{
		$module = new $class();
		self::assertInstanceOf(IDbModule::class, $module);
		self::assertSame('', $module->getConnectionID());

		$module->setConnectionID('db');
		self::assertSame('db', $module->getConnectionID());
	}

	/**
	 * @dataProvider moduleProvider
	 */
	public function testConnectionIdWithInitializedTrait(string $class, string $initializedClass)
	{
		$module = new $initializedClass();
		$module->setConnectionID('db');
		self::assertSame('db', $module->getConnectionID());

		$module->init(null);
		$this->expectException(TInvalidOperationException::class);
		$module->setConnectionID('other');
	}
}
