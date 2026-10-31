<?php

namespace Prado\Test\Unit\Shell\Actions;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Shell\Actions\TFlushCachesAction;
use Prado\Shell\TShellWriter;
use Prado\TApplicationComponent;
use Prado\Test\Unit\Harness\TTestApplication;

/**
 * `cache/flush-all` deletes the Performance-mode fx-event cache file.
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
		$this->_action->setWriter(new TShellWriter($this->_text));
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
}
