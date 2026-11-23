<?php

/**
 * TAssetManagerMockTrait trait file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Traits;

use PHPUnit\Framework\MockObject\MockObject;
use Prado\Prado;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\TAssetManager;

/**
 * TAssetManagerMockTrait replaces the application's asset manager with a mock, so a control
 * that publishes its assets writes no files.
 *
 * {@see mockAssetManager()} installs the mock: `publishFilePath()` returns `/assets/` and the
 * path's base name, and `getPublishedPath()` returns the published base (default `/published`),
 * `/`, and the base name.
 * {@see restoreAssetManager()} puts the original asset manager back; call it in `tearDown()`.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
trait TAssetManagerMockTrait
{
	/** @var bool whether {@see mockAssetManager()} saved the original asset manager. */
	private bool $_assetManagerSaved = false;

	/** @var ?TAssetManager the asset manager {@see mockAssetManager()} replaced. */
	private ?TAssetManager $_savedAssetManager = null;

	/**
	 * Installs a mock asset manager on the application.
	 * @param string[] $methods further methods to mock; the test sets their behavior.
	 * @param string $publishedBase the folder `getPublishedPath()` places a published file in.
	 * @return MockObject&TAssetManager the mock.
	 */
	protected function mockAssetManager(array $methods = [], string $publishedBase = '/published'): TAssetManager
	{
		$app = Prado::getApplication();
		if (!$this->_assetManagerSaved) {
			$this->_savedAssetManager = PradoUnit::getProp($app, '_assetManager');
			$this->_assetManagerSaved = true;
		}
		$manager = $this->getMockBuilder(TAssetManager::class)
			->onlyMethods(array_unique(array_merge(['publishFilePath', 'getPublishedPath'], $methods)))
			->getMock();
		$manager->method('publishFilePath')->willReturnCallback(fn ($path) => '/assets/' . basename($path));
		$manager->method('getPublishedPath')->willReturnCallback(fn ($path) => $publishedBase . '/' . basename($path));
		$app->setAssetManager($manager);
		return $manager;
	}

	/**
	 * Restores the asset manager that {@see mockAssetManager()} replaced.
	 */
	protected function restoreAssetManager(): void
	{
		if ($this->_assetManagerSaved) {
			PradoUnit::setProp(Prado::getApplication(), '_assetManager', $this->_savedAssetManager);
			$this->_savedAssetManager = null;
			$this->_assetManagerSaved = false;
		}
	}
}
