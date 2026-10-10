<?php

namespace Prado\Test\Unit;

use PHPUnit\Framework\TestCase;
use Prado\TApplicationStatePersister;
use Prado\Test\Unit\Harness\Caching\TTestArrayCache;
use Prado\Test\Unit\Harness\TTestApplication;

/**
 * Tests for {@see \Prado\TApplicationStatePersister}.
 *
 * Each test runs against a {@see TTestApplication} rooted in its own temporary
 * directory so the state file lives in a disposable runtime path.
 */
class TApplicationStatePersisterTest extends TestCase
{
	private string $_tmpDir;
	private TTestApplication $_app;
	private TApplicationStatePersister $_persister;

	protected function setUp(): void
	{
		$this->_tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tasp_' . uniqid('', true);
		mkdir($this->_tmpDir, 0o777, true);
		$this->_app = new TTestApplication($this->_tmpDir);
		$this->_persister = new TApplicationStatePersister();
	}

	protected function tearDown(): void
	{
		$this->_app->restoreApplication();
		$runtime = $this->_app->getRuntimePath();
		foreach ([$runtime . DIRECTORY_SEPARATOR . 'global.cache', $runtime . DIRECTORY_SEPARATOR . 'fxevent.cache'] as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}
		if (is_dir($runtime)) {
			rmdir($runtime);
		}
		rmdir($this->_tmpDir);
	}

	private function stateFile(): string
	{
		return $this->_app->getRuntimePath() . DIRECTORY_SEPARATOR . 'global.cache';
	}

	public function testInit_registersWithApplication(): void
	{
		$this->_persister->init(null);
		$this->assertSame($this->_persister, $this->_app->getApplicationStatePersister());
	}

	public function testLoad_noCacheNoFile_returnsNull(): void
	{
		$this->assertNull($this->_persister->load());
	}

	public function testLoad_noCache_readsStateFile(): void
	{
		file_put_contents($this->stateFile(), serialize(['a' => 1]));
		$this->assertSame(['a' => 1], $this->_persister->load());
	}

	public function testLoad_cacheHit_prefersCacheOverFile(): void
	{
		$cache = new TTestArrayCache();
		$cache->set(TApplicationStatePersister::CACHE_NAME, serialize(['from' => 'cache']));
		$this->_app->setCache($cache);
		file_put_contents($this->stateFile(), serialize(['from' => 'file']));

		$this->assertSame(['from' => 'cache'], $this->_persister->load());
	}

	public function testLoad_cacheMiss_fallsBackToFile(): void
	{
		$this->_app->setCache(new TTestArrayCache());
		file_put_contents($this->stateFile(), serialize(['from' => 'file']));

		$this->assertSame(['from' => 'file'], $this->_persister->load());
	}

	public function testSave_noCache_writesStateFile(): void
	{
		$this->_persister->save(['x' => 2]);
		$this->assertSame(serialize(['x' => 2]), file_get_contents($this->stateFile()));
	}

	public function testSave_cacheChanged_writesCacheAndFile(): void
	{
		$cache = new TTestArrayCache();
		$this->_app->setCache($cache);

		$this->_persister->save(['x' => 3]);

		$this->assertSame(serialize(['x' => 3]), $cache->get(TApplicationStatePersister::CACHE_NAME));
		$this->assertSame(serialize(['x' => 3]), file_get_contents($this->stateFile()));
	}

	public function testSave_cacheUnchanged_skipsFileWrite(): void
	{
		$cache = new TTestArrayCache();
		$cache->set(TApplicationStatePersister::CACHE_NAME, serialize(['x' => 4]));
		$this->_app->setCache($cache);

		$this->_persister->save(['x' => 4]);

		$this->assertFileDoesNotExist($this->stateFile());
	}

	public function testSaveThenLoad_roundTrips(): void
	{
		$state = ['k' => ['nested' => true], 'n' => 5];
		$this->_persister->save($state);
		$this->assertSame($state, $this->_persister->load());
	}
}
