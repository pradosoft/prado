<?php

namespace Prado\Test\Unit;

use PHPUnit\Framework\TestCase;
use Prado\Prado;
use Prado\TApplicationComponent;
use Prado\TApplicationMode;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\Harness\Traits\TOutputBufferRestorationTrait;
use Prado\Util\TComposerReflection;
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
 * Application component with an fx event, used by the Normal-mode test.
 */
class TApplicationComponentTest_NormalFxComponent extends TApplicationComponent
{
	public function fxApplicationComponentTestNormalEvent($sender, $param)
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

	public function testClassFxEvents_normalModeIgnoresTheCacheFile(): void
	{
		$this->_app->setMode(TApplicationMode::Normal);
		$cacheFile = $this->_app->getRuntimePath() . DIRECTORY_SEPARATOR . TApplicationComponent::FX_CACHE_FILE;
		$stale = serialize([TApplicationComponentTest_NormalFxComponent::class => ['fxStale']]);
		file_put_contents($cacheFile, $stale);

		$component = new TApplicationComponentTest_NormalFxComponent();
		$fx = PradoUnit::invoke($component, 'getClassFxEvents', $component);

		$this->assertContains('fxApplicationComponentTestNormalEvent', $fx);
		$this->assertNotContains('fxStale', $fx);
		$this->assertSame($stale, file_get_contents($cacheFile));
		$component->unlisten();
	}

	public function testReadFxCache_returnsTheListsOrAnEmptyArray(): void
	{
		$cacheFile = $this->_app->getRuntimePath() . DIRECTORY_SEPARATOR . TApplicationComponent::FX_CACHE_FILE;
		$this->assertSame([], PradoUnit::invoke(TApplicationComponent::class, 'readFxCache', $cacheFile));

		file_put_contents($cacheFile, serialize(['Some\\Component' => ['fxA']]));
		$this->assertSame(['Some\\Component' => ['fxA']], PradoUnit::invoke(TApplicationComponent::class, 'readFxCache', $cacheFile));

		file_put_contents($cacheFile, 'not serialized');
		$this->assertSame([], PradoUnit::invoke(TApplicationComponent::class, 'readFxCache', $cacheFile));

		file_put_contents($cacheFile, serialize(new \ArrayObject(['x'])));
		$this->assertSame([], PradoUnit::invoke(TApplicationComponent::class, 'readFxCache', $cacheFile));
	}

	public function testReadFxCache_discardsAFileOlderThanTheComposerManifests(): void
	{
		$manifestsTime = TComposerReflection::getInstalledManifestsTime();
		if ($manifestsTime === null) {
			$this->markTestSkipped('No Composer installed.json is registered.');
		}
		$cacheFile = $this->_app->getRuntimePath() . DIRECTORY_SEPARATOR . TApplicationComponent::FX_CACHE_FILE;
		file_put_contents($cacheFile, serialize(['Some\\Component' => ['fxA']]));

		touch($cacheFile, $manifestsTime - 1);
		$this->assertSame([], PradoUnit::invoke(TApplicationComponent::class, 'readFxCache', $cacheFile));

		touch($cacheFile, $manifestsTime);
		$this->assertSame(['Some\\Component' => ['fxA']], PradoUnit::invoke(TApplicationComponent::class, 'readFxCache', $cacheFile));
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

}
