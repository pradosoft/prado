<?php

/**
 * TApplicationApplyConfigurationExtrasTest class file.
 *
 * Covers the class-map and exception-message registration that
 * {@see \Prado\TApplication::applyConfiguration()} performs from a parsed
 * {@see \Prado\TApplicationConfiguration}: the {@see \Prado\TApplicationConfiguration::getClassMap()}
 * map is merged via {@see \Prado\Prado::registerClassMap()}, and each
 * {@see \Prado\TApplicationConfiguration::getErrorMessages()} path is registered via
 * {@see \Prado\Exceptions\TException::addMessageFile()}.
 *
 * The global {@see \Prado\Prado::$classMap} and {@see \Prado\Exceptions\TException}
 * message-file list are snapshotted and restored so no test leaks process state.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Prado;
use Prado\TApplication;
use Prado\Exceptions\TException;

class TApplicationApplyConfigurationExtrasTest extends PHPUnit\Framework\TestCase
{
	/** Builds a bare TApplication with the minimum state applyConfiguration() reads. */
	private function bareApp(): TApplication
	{
		$app = (new \ReflectionClass(TApplication::class))->newInstanceWithoutConstructor();
		PradoUnit::setProp($app, '_configType', TApplication::CONFIG_TYPE_XML);
		PradoUnit::setProp($app, '_basePath', sys_get_temp_dir());
		PradoUnit::setProp($app, '_pageServiceID', 'page');
		PradoUnit::setProp($app, '_services', []);
		PradoUnit::setProp($app, '_parameters', new \Prado\Collections\TMap());
		PradoUnit::setProp($app, '_modules', []);
		PradoUnit::setProp($app, '_lazyModules', []);
		return $app;
	}

	/** A no-op config mock exposing the given class map and error-message paths. */
	private function configMock(array $classMap = [], array $errorMessages = []): \Prado\TApplicationConfiguration
	{
		$config = $this->createMock(\Prado\TApplicationConfiguration::class);
		$config->method('getIsEmpty')->willReturn(false);
		$config->method('getAliases')->willReturn([]);
		$config->method('getUsings')->willReturn([]);
		$config->method('getClassMap')->willReturn($classMap);
		$config->method('getErrorMessages')->willReturn($errorMessages);
		$config->method('getProperties')->willReturn([]);
		$config->method('getServices')->willReturn([]);
		$config->method('getParameters')->willReturn([]);
		$config->method('getModules')->willReturn([]);
		$config->method('getExternalConfigurations')->willReturn([]);
		return $config;
	}

	public function testApplyConfiguration_registersErrorMessageFiles(): void
	{
		$saved = PradoUnit::getStaticProp(TException::class, '_messageFiles');
		try {
			$file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado_appcfg_messages.txt';
			$this->bareApp()->applyConfiguration($this->configMock([], [$file]), false);

			$this->assertContains(
				$file,
				PradoUnit::getStaticProp(TException::class, '_messageFiles'),
				'applyConfiguration() must register each getErrorMessages() path via TException::addMessageFile()'
			);
		} finally {
			PradoUnit::setStaticProp(TException::class, '_messageFiles', $saved);
		}
	}

	public function testApplyConfiguration_registersClassMap(): void
	{
		$saved = Prado::$classMap;
		try {
			$this->bareApp()->applyConfiguration(
				$this->configMock(['TAppCfgFoo' => 'Vendor\\AppCfgFoo'], []),
				false
			);

			$this->assertArrayHasKey('TAppCfgFoo', Prado::$classMap);
			$this->assertSame('Vendor\\AppCfgFoo', Prado::$classMap['TAppCfgFoo']);
		} finally {
			Prado::$classMap = $saved;
		}
	}

	public function testApplyConfiguration_emptyClassMapIsNoOp(): void
	{
		$saved = Prado::$classMap;
		try {
			$this->bareApp()->applyConfiguration($this->configMock([], []), false);
			$this->assertSame($saved, Prado::$classMap);
		} finally {
			Prado::$classMap = $saved;
		}
	}
}
