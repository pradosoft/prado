<?php

namespace Prado\Test\Unit\Shell\Actions;

use PHPUnit\Framework\TestCase;
use Prado\Caching\ICache;
use Prado\IO\TTextWriter;
use Prado\Shell\Actions\TFlushCachesAction;
use Prado\Shell\TShellWriter;
use Prado\TApplicationComponent;
use Prado\TModule;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\PradoUnit;

/**
 * An {@see ICache} module that counts its flushes.
 */
class TFlushCachesActionTestCache extends TModule implements ICache
{
	public int $flushes = 0;

	public static function getIsAvailable(): bool
	{
		return true;
	}

	public function get($id)
	{
		return false;
	}

	public function set($id, $value, $expire = 0, $dependency = null)
	{
		return true;
	}

	public function add($id, $value, $expire = 0, $dependency = null)
	{
		return true;
	}

	public function delete($id)
	{
		return true;
	}

	public function flush()
	{
		$this->flushes++;
		return true;
	}
}

/**
 * Tests for {@see TFlushCachesAction}: listing and flushing the application's
 * {@see ICache} modules, and deleting the Performance-mode fx-event cache file.
 */
class TFlushCachesActionTest extends TestCase
{
	private string $_tmpDir;
	private TTestApplication $_app;
	private TTextWriter $_text;
	private TFlushCachesAction $_action;

	protected function setUp(): void
	{
		$this->_tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'flush_caches_' . uniqid('', true);
		mkdir($this->_tmpDir, 0o777, true);
		$this->_app = new TTestApplication($this->_tmpDir);
		$this->_text = new TTextWriter();
		$this->_action = new TFlushCachesAction();
		$writer = new TShellWriter($this->_text);
		$writer->setColorSupported(false);
		$this->_action->setWriter($writer);
	}

	protected function tearDown(): void
	{
		$runtime = $this->_app->getRuntimePath();
		$this->_app->restoreApplication();
		if (is_file($cacheFile = $this->cacheFile($runtime))) {
			unlink($cacheFile);
		}
		if (is_dir($runtime)) {
			rmdir($runtime);
		}
		rmdir($this->_tmpDir);
	}

	/**
	 * Registers only the eager cache module `eagerCache`.
	 */
	private function addEagerCache(): TFlushCachesActionTestCache
	{
		$this->_app->setModule('eagerCache', $eager = new TFlushCachesActionTestCache());
		return $eager;
	}

	/**
	 * Registers only the lazy cache module `lazyCache`.
	 */
	private function addLazyCache(): void
	{
		PradoUnit::invoke($this->_app, 'setLazyModule', 'lazyCache', [TFlushCachesActionTestCache::class, [], null]);
		$this->_app->setModule('lazyCache', null);
	}

	/**
	 * Registers an eager cache module `eagerCache` and a lazy cache module `lazyCache`.
	 * @return TFlushCachesActionTestCache the eager cache module.
	 */
	private function addCaches(): TFlushCachesActionTestCache
	{
		$this->_app->setModule('eagerCache', $eager = new TFlushCachesActionTestCache());
		PradoUnit::invoke($this->_app, 'setLazyModule', 'lazyCache', [TFlushCachesActionTestCache::class, [], null]);
		$this->_app->setModule('lazyCache', null);
		return $eager;
	}

	private function cacheFile(?string $runtime = null): string
	{
		return ($runtime ?? $this->_app->getRuntimePath()) . DIRECTORY_SEPARATOR . TApplicationComponent::FX_CACHE_FILE;
	}

	public function testFlushAllDeletesTheFxEventCache(): void
	{
		file_put_contents($this->cacheFile(), serialize([]));

		$this->assertTrue($this->_action->actionFlushAll(['cache/flush-all']));

		$this->assertFileDoesNotExist($this->cacheFile());
		$output = $this->_text->flush();
		$this->assertStringContainsString(TApplicationComponent::FX_CACHE_FILE, $output);
		$this->assertStringNotContainsString('no caches were found', $output);
	}

	public function testFlushAllWithoutCachesReportsNone(): void
	{
		$this->assertTrue($this->_action->actionFlushAll(['cache/flush-all']));

		$output = $this->_text->flush();
		$this->assertStringContainsString('no caches were found', $output);
		$this->assertStringNotContainsString(TApplicationComponent::FX_CACHE_FILE, $output);
	}

	public function testFlushAllFlushesEveryCache(): void
	{
		$eager = $this->addCaches();

		$this->assertTrue($this->_action->actionFlushAll(['cache/flush-all']));

		$lazy = $this->_app->getModule('lazyCache');
		$this->assertSame(1, $eager->flushes);
		$this->assertSame(1, $lazy->flushes);
		$output = $this->_text->flush();
		$this->assertStringContainsString('eagerCache (' . TFlushCachesActionTestCache::class . ')', $output);
		$this->assertStringContainsString('lazyCache (' . TFlushCachesActionTestCache::class . ')', $output);
		$this->assertStringNotContainsString('no caches were found', $output);
	}

	public function testFlushAllKeepsAnUndeletableFxEventCache(): void
	{
		file_put_contents($this->cacheFile(), serialize([]));
		chmod($runtime = $this->_app->getRuntimePath(), 0o555);
		try {
			$this->assertTrue($this->_action->actionFlushAll(['cache/flush-all']));
		} finally {
			chmod($runtime, 0o777);
		}

		$this->assertFileExists($this->cacheFile());
		$this->assertStringContainsString('no caches were found', $this->_text->flush());
	}

	public function testFlushFlushesTheNamedCaches(): void
	{
		$eager = $this->addCaches();

		$this->assertTrue($this->_action->actionFlush(['cache/flush', 'lazyCache']));

		$this->assertSame(0, $eager->flushes);
		$this->assertSame(1, $this->_app->getModule('lazyCache')->flushes);
		$output = $this->_text->flush();
		$this->assertStringContainsString('lazyCache (' . TFlushCachesActionTestCache::class . ')', $output);
		$this->assertStringNotContainsString('eagerCache', $output);

		$this->assertTrue($this->_action->actionFlush(['cache/flush', 'eagerCache', 'lazyCache']));

		$this->assertSame(1, $eager->flushes);
		$this->assertSame(2, $this->_app->getModule('lazyCache')->flushes);
	}

	public function testFlushWithoutAMatchingCacheReportsNone(): void
	{
		$eager = $this->addCaches();

		$this->assertTrue($this->_action->actionFlush(['cache/flush', 'missingCache']));

		$this->assertSame(0, $eager->flushes);
		$this->assertStringContainsString('no cache was found', $this->_text->flush());
	}

	public function testIndexListsTheCaches(): void
	{
		$this->addCaches();

		$this->assertTrue($this->_action->actionIndex(['cache']));

		$output = $this->_text->flush();
		$this->assertStringContainsString('eagerCache (' . TFlushCachesActionTestCache::class . ')', $output);
		$this->assertStringContainsString('lazyCache (' . TFlushCachesActionTestCache::class . ')', $output);
		$this->assertStringNotContainsString('no caches were found', $output);
	}

	public function testIndexWithoutCachesReportsNone(): void
	{
		$this->assertTrue($this->_action->actionIndex(['cache']));

		$this->assertStringContainsString('no caches were found', $this->_text->flush());
	}

	public function testGetExampleIsFlushAll(): void
	{
		$this->assertSame('cache/flush-all', $this->_action->getExample());
	}

	public function testOneEagerCache(): void
	{
		$eager = $this->addEagerCache();

		$this->assertTrue($this->_action->actionIndex(['cache']));
		$this->assertTrue($this->_action->actionFlush(['cache/flush', 'eagerCache']));
		$this->assertTrue($this->_action->actionFlushAll(['cache/flush-all']));

		$this->assertSame(2, $eager->flushes);
		$output = $this->_text->flush();
		$this->assertSame(3, substr_count($output, 'eagerCache (' . TFlushCachesActionTestCache::class . ')'));
		$this->assertStringNotContainsString('was found', $output);
		$this->assertStringNotContainsString('were found', $output);
	}

	public function testOneLazyCache(): void
	{
		$this->addLazyCache();
		$this->assertTrue($this->_action->actionIndex(['cache']));

		$this->_app->restoreApplication();
		$this->_app = new TTestApplication($this->_tmpDir);
		$this->addLazyCache();
		$this->assertTrue($this->_action->actionFlush(['cache/flush', 'lazyCache']));
		$this->assertSame(1, $this->_app->getModule('lazyCache')->flushes);

		$this->_app->restoreApplication();
		$this->_app = new TTestApplication($this->_tmpDir);
		$this->addLazyCache();
		$this->assertTrue($this->_action->actionFlushAll(['cache/flush-all']));
		$this->assertSame(1, $this->_app->getModule('lazyCache')->flushes);

		$output = $this->_text->flush();
		$this->assertSame(3, substr_count($output, 'lazyCache (' . TFlushCachesActionTestCache::class . ')'));
	}

	public function testFlushWithOneUnnamedCacheReportsNone(): void
	{
		$eager = $this->addEagerCache();

		$this->assertTrue($this->_action->actionFlush(['cache/flush', 'missingCache']));

		$this->assertSame(0, $eager->flushes);
		$this->assertStringContainsString('no cache was found', $this->_text->flush());
	}

	public function testFlushWithoutCachesReportsNone(): void
	{
		$this->assertTrue($this->_action->actionFlush(['cache/flush', 'missingCache']));

		$this->assertStringContainsString('no cache was found', $this->_text->flush());
	}
}
