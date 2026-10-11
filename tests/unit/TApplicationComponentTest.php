<?php

namespace Prado\Test\Unit;

use PHPUnit\Framework\TestCase;
use Prado\Prado;
use Prado\TApplicationComponent;
use Prado\TApplicationMode;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\Harness\Traits\TOutputBufferRestorationTrait;
use Prado\Web\TAssetManager;

/**
 * Application component with an fx event, cached by the Performance-mode test.
 */
class TApplicationComponentTest_FxComponent extends TApplicationComponent
{
	public function fxApplicationComponentTestEvent($sender, $param)
	{
	}
}

/**
 * Asset manager that records the published path instead of copying files.
 */
class TApplicationComponentTest_AssetManager extends TAssetManager
{
	public array $published = [];

	public function publishFilePath($path, $checkTimestamp = false)
	{
		$this->published[] = [$path, $checkTimestamp];
		return 'url:' . $path;
	}
}

/**
 * Tests for {@see \Prado\TApplicationComponent}.
 */
class TApplicationComponentTest extends TestCase
{
	use TOutputBufferRestorationTrait;

	private string $_tmpDir;
	private TTestApplication $_app;

	protected function setUp(): void
	{
		$this->saveOutputBufferLevel();
		$this->_tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tac_comp_' . uniqid('', true);
		mkdir($this->_tmpDir, 0o777, true);
		$this->_app = new TTestApplication($this->_tmpDir);
	}

	protected function tearDown(): void
	{
		$this->_app->restoreApplication();
		$runtime = $this->_app->getRuntimePath();
		$cacheFile = $runtime . DIRECTORY_SEPARATOR . TApplicationComponent::FX_CACHE_FILE;
		if (is_file($cacheFile)) {
			unlink($cacheFile);
		}
		if (is_dir($runtime)) {
			rmdir($runtime);
		}
		rmdir($this->_tmpDir);
		$this->restoreOutputBufferLevel();
	}

	public function testGetAutoGlobalListen_isTrue(): void
	{
		$this->assertTrue((new TApplicationComponent())->getAutoGlobalListen());
	}

	public function testClassFxEvents_performanceMode_writesCacheFile(): void
	{
		$this->_app->setMode(TApplicationMode::Performance);
		$cacheFile = $this->_app->getRuntimePath() . DIRECTORY_SEPARATOR . TApplicationComponent::FX_CACHE_FILE;

		$component = new TApplicationComponentTest_FxComponent();

		$this->assertFileExists($cacheFile);
		$cached = unserialize(file_get_contents($cacheFile));
		$this->assertArrayHasKey(TApplicationComponentTest_FxComponent::class, $cached);
		$this->assertContains('fxApplicationComponentTestEvent', $cached[TApplicationComponentTest_FxComponent::class]);
		$component->unlisten();
	}

	public function testAccessors_delegateToApplication(): void
	{
		$component = new TApplicationComponent();
		$this->assertSame($this->_app, $component->getApplication());
		$this->assertSame(Prado::getApplication()->getService(), $component->getService());
		$this->assertSame($this->_app->getRequest(), $component->getRequest());
		$this->assertSame($this->_app->getResponse(), $component->getResponse());
		$this->assertSame($this->_app->getSession(), $component->getSession());
		$this->assertSame($this->_app->getUser(), $component->getUser());
	}

	public function testPublishFilePath_delegatesToAssetManager(): void
	{
		$this->_app->setAssetManager($manager = new TApplicationComponentTest_AssetManager());

		$url = (new TApplicationComponent())->publishFilePath('/some/path', true);

		$this->assertSame('url:/some/path', $url);
		$this->assertSame([['/some/path', true]], $manager->published);
	}

	public function testPublishAsset_defaultClass_resolvesRelativeToOwnClassFile(): void
	{
		$this->_app->setAssetManager($manager = new TApplicationComponentTest_AssetManager());

		(new TApplicationComponentTest_FxComponent())->publishAsset('asset.js');

		$this->assertSame(__DIR__ . DIRECTORY_SEPARATOR . 'asset.js', $manager->published[0][0]);
		$this->assertFalse($manager->published[0][1]);
	}

	public function testPublishAsset_explicitClass_resolvesRelativeToThatClassFile(): void
	{
		$this->_app->setAssetManager($manager = new TApplicationComponentTest_AssetManager());

		(new TApplicationComponent())->publishAsset('x.css', TAssetManager::class);

		$expected = dirname((new \ReflectionClass(TAssetManager::class))->getFileName()) . DIRECTORY_SEPARATOR . 'x.css';
		$this->assertSame($expected, $manager->published[0][0]);
	}
}
