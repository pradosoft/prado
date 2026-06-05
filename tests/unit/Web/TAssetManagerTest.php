<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TIOException;
use Prado\Prado;
use Prado\TApplication;
use Prado\Web\TAssetManager;

class TAssetManagerTest extends PHPUnit\Framework\TestCase
{
	public static $app = null;
	public static $assetDir = null;

	public static $class = null;
	
	protected function getTestClass(): string
	{
		return TAssetManager::class;
	}
	
	protected function newAssetManager(...$arg)
	{
		$class = $this->getTestClass();
		return new $class(...$arg);
	}

	protected function setUp(): void
	{
		// Fake environment variables needed to determine path
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['SERVER_PORT'] = '80';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/demos/personal/index.php?page=Links';
		$_SERVER['SCRIPT_NAME'] = '/demos/personal/index.php';
		$_SERVER['PHP_SELF'] = '/demos/personal/index.php';
		$_SERVER['QUERY_STRING'] = 'page=Links';
		$_SERVER['SCRIPT_FILENAME'] = __FILE__;
		$_SERVER['PATH_INFO'] = __FILE__;
		$_SERVER['HTTP_REFERER'] = 'https://github.com/pradosoft/prado';
		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US; rv:1.8.1.3) Gecko/20070309 Firefox/2.0.0.3';
		$_SERVER['REMOTE_HOST'] = 'localhost';

		if (self::$app === null) {
			self::$app = new TApplication(__DIR__ . '/app');
		}

		if (self::$assetDir === null) {
			// Use DIRECTORY_SEPARATOR so the path matches what framework internals produce on all OSes.
			self::$assetDir = __DIR__ . DIRECTORY_SEPARATOR . 'assets';
		}
		// Make asset directory if not exists
		if (!file_exists(self::$assetDir)) {
			if (is_writable(dirname(self::$assetDir))) {
				mkdir(self::$assetDir, Prado::getDefaultDirPermissions()) ;
			} else {
				throw new Exception('Directory ' . dirname(self::$assetDir) . ' is not writable');
			}
		} elseif (!is_dir(self::$assetDir)) {
			throw new Exception(self::$assetDir . ' exists and is not a directory');
		}
		// Define an alias to asset directory
		prado::setPathofAlias('AssetAlias', self::$assetDir);
			
		self::$class = $this->getTestClass();
	}

	private function removeDirectory($dir)
	{
		// Let's be sure $dir is a directory to avoir any error. Clear the cache !
		clearstatcache();
		if (is_dir($dir)) {
			foreach (scandir($dir) as $content) {
				if ($content === '.' || $content === '..') {
					continue;
				} // skip . and ..
				$content = $dir . '/' . $content;
				if (is_dir($content)) {
					$this->removeDirectory($content);
				} // Recursivly remove directories
				else {
					unlink($content);
				} // Remove file
			}
			// Now, directory should be empty, remove it
			rmdir($dir);
		}
	}

	protected function tearDown(): void
	{
		// Make some cleaning :)
		$this->removeDirectory(self::$assetDir);
	}

	/**
	 * Probes whether the platform can create a relative symbolic link that resolves,
	 * which is what the LinkAssets tests require. On Windows this depends on the
	 * process privilege and is frequently unavailable on CI runners.
	 */
	protected function symlinksSupported(): bool
	{
		$target = self::$assetDir . DIRECTORY_SEPARATOR . '__symprobe_target';
		$link = self::$assetDir . DIRECTORY_SEPARATOR . '__symprobe_link';
		// Cleanup is throwable-tolerant: the application error handler can turn even
		// an unlink()/symlink() warning into an exception under '@'.
		$cleanup = function ($p) {
			try {
				if (is_link($p) || file_exists($p)) {
					@unlink($p);
				}
			} catch (\Throwable $e) {
			}
		};
		$cleanup($link);
		$cleanup($target);
		$ok = false;
		try {
			@file_put_contents($target, 'probe');
			$ok = @symlink('__symprobe_target', $link) && is_link($link) && file_exists($link);
		} catch (\Throwable $e) {
			$ok = false;
		}
		$cleanup($link);
		$cleanup($target);
		return $ok;
	}

	/**
	 * Skips the calling test when relative symbolic links are not supported.
	 */
	protected function requireSymlinks(): void
	{
		if (!$this->symlinksSupported()) {
			$this->markTestSkipped('Relative symbolic links are not supported on this platform.');
		}
	}

	/**
	 * Skips the calling test when the platform does not honor Unix file permission
	 * modes (e.g. Windows), where chmod does not produce the asserted bits.
	 */
	protected function requireFileModes(): void
	{
		if (DIRECTORY_SEPARATOR === '\\') {
			$this->markTestSkipped('Unix file permission modes are not supported on this platform.');
		}
	}

	public function testInit()
	{
		$manager = $this->newAssetManager();

		$manager->init(null);

		self::assertEquals(self::$assetDir, $manager->getBasePath());
		self::assertEquals($manager, self::$app->getAssetManager());

		// No, remove asset directory, and catch the exception
		if (is_dir(self::$assetDir)) {
			$this->removeDirectory(self::$assetDir);
		}
		try {
			$manager->init(null);
			self::fail('Expected TConfigurationException not thrown');
		} catch (TConfigurationException $e) {
		}
	}

	public function testSetBasePath()
	{
		$manager = $this->newAssetManager();
		// First try, invalid directory
		try {
			$manager->setBasePath('invalid');
			self::fail('Expected TInvalidDataValueException not thrown');
		} catch (TInvalidDataValueException $e) {
		}

		// Next, standard asset directory, should work

		$manager->setBasePath('AssetAlias');
		self::assertEquals(self::$assetDir, $manager->getBasePath());

		// A failed set must not corrupt the previously valid base path.
		try {
			$manager->setBasePath('invalid');
			self::fail('Expected TInvalidDataValueException not thrown');
		} catch (TInvalidDataValueException $e) {
		}
		self::assertEquals(self::$assetDir, $manager->getBasePath());

		// Finally, test to change after init
		$manager->init(null);
		try {
			$manager->setBasePath('test');
			self::fail('Expected TInvalidOperationException not thrown');
		} catch (TInvalidOperationException $e) {
		}
	}

	public function testSetBaseUrl()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/assets/');
		self::assertEquals("/assets", $manager->getBaseUrl());

		$manager->init(null);
		try {
			$manager->setBaseUrl('/test');
			self::fail('Expected TInvalidOperationException not thrown');
		} catch (TInvalidOperationException $e) {
		}
	}

	public function testPublishFilePath()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		// Try to publish a single file
		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$publishedUrl = $manager->publishFilePath($fileToPublish);
		$publishedFile = self::$assetDir . $publishedUrl;
		self::assertEquals(str_replace('\\', '/', $publishedFile), str_replace('\\', '/', $manager->getPublishedPath($fileToPublish)));
		self::assertEquals($publishedUrl, $manager->getPublishedUrl($fileToPublish));
		self::assertTrue(is_file($publishedFile));

		//  try to publish invalid file
		try {
			$manager->publishFilePath('invalid_file');
			self::fail('Expected TInvalidDataValueException not thrown');
		} catch (TInvalidDataValueException $e) {
		}
	}

	public function testPublishFilePathWithDirectory()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		// Try to publish a directory
		$dirToPublish = __DIR__ . '/data';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;
		self::assertEquals(str_replace('\\', '/', $publishedDir), str_replace('\\', '/', $manager->getPublishedPath($dirToPublish)));
		self::assertEquals($publishedUrl, $manager->getPublishedUrl($dirToPublish));
		self::assertTrue(is_dir($publishedDir));
		self::assertTrue(is_file($publishedDir . '/pradoheader.gif'));
	}

	public function testPublishTarFile()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$tarFile = __DIR__ . '/data/aTarFile.tar';
		$md5File = __DIR__ . '/data/aTarFile.md5';

		// First, try with bad md5
		try {
			$manager->publishTarFile($tarFile, 'badMd5File');
			self::fail('Expected TInvalidDataValueException not thrown');
		} catch (TInvalidDataValueException $e) {
		}

		// Then, try with real md5 file
		$publishedUrl = $manager->publishTarFile($tarFile, $md5File);
		$publishedDir = self::$assetDir . $publishedUrl;
		self::assertTrue(is_dir($publishedDir));
		self::assertTrue(is_file($publishedDir . '/pradoheader.gif'));
		self::assertTrue(is_file($publishedDir . '/aTarFile.md5'));
	}

	public function testLinkAssetsEnabled()
	{
		$this->requireSymlinks();
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setLinkAssets(true);
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$publishedUrl = $manager->publishFilePath($fileToPublish);
		$publishedFile = self::$assetDir . $publishedUrl;

		self::assertTrue(is_link($publishedFile) || file_exists($publishedFile));
	}

	public function testLinkAssetsDisabledCopyInstead()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setLinkAssets(false);
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$publishedUrl = $manager->publishFilePath($fileToPublish);
		$publishedFile = self::$assetDir . $publishedUrl;

		self::assertTrue(is_file($publishedFile));
		self::assertFalse(is_link($publishedFile));
	}

	public function testLinkAssetsFallbackOnFailure()
	{
		$this->requireSymlinks();
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setLinkAssets(true);
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$publishedUrl = $manager->publishFilePath($fileToPublish);
		$publishedFile = self::$assetDir . $publishedUrl;

		self::assertTrue(is_file($publishedFile) || is_link($publishedFile));
	}

	public function testForceCopyDirectory()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setForceCopy(true);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		self::assertTrue(is_dir($publishedDir));
		self::assertTrue(is_file($publishedDir . '/js/app.js'));
	}

	public function testForceCopyOverridesTimestamp()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setForceCopy(true);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$manager->publishFilePath($dirToPublish);

		$publishedDir = self::$assetDir . $manager->getPublishedUrl($dirToPublish);
		$originalMtime = filemtime($publishedDir . '/js/app.js');

		sleep(1);
		touch($publishedDir . '/js/app.js', time());

		$manager->publishFilePath($dirToPublish);
		$newMtime = filemtime($publishedDir . '/js/app.js');

		self::assertEqualsWithDelta(time(), $newMtime, 2);
	}

	/**
	 * Regression: forceCopy must re-copy a single published file even when the
	 * destination is newer than the source. Previously forceCopy was ignored
	 * for single files (copyFile only checked the modification time).
	 */
	public function testForceCopyOverwritesNewerSingleFile()
	{
		$source = __DIR__ . '/data/pradoheader.gif';

		// First publish (plain copy).
		$manager1 = $this->newAssetManager();
		$manager1->setBaseUrl('/');
		$manager1->init(null);
		$manager1->publishFilePath($source);
		$publishedFile = $manager1->getPublishedPath($source);
		self::assertTrue(is_file($publishedFile));

		// Corrupt the published copy and make it newer than the source.
		file_put_contents($publishedFile, 'corrupted');
		touch($publishedFile, time() + 10);
		self::assertNotEquals(file_get_contents($source), file_get_contents($publishedFile));

		// A fresh manager with forceCopy must restore the source content
		// despite the destination being newer.
		$manager2 = $this->newAssetManager();
		$manager2->setBaseUrl('/');
		$manager2->setForceCopy(true);
		$manager2->init(null);
		$manager2->publishFilePath($source);

		self::assertEquals(file_get_contents($source), file_get_contents($publishedFile));
	}

	/**
	 * Counterpart to the forceCopy regression: without forceCopy, a single
	 * published file that is newer than the source is left untouched.
	 */
	public function testSingleFileNotOverwrittenWhenNewerWithoutForceCopy()
	{
		$source = __DIR__ . '/data/pradoheader.gif';

		$manager1 = $this->newAssetManager();
		$manager1->setBaseUrl('/');
		$manager1->init(null);
		$manager1->publishFilePath($source);
		$publishedFile = $manager1->getPublishedPath($source);

		file_put_contents($publishedFile, 'corrupted');
		touch($publishedFile, time() + 10);

		$manager2 = $this->newAssetManager();
		$manager2->setBaseUrl('/');
		$manager2->init(null);
		$manager2->publishFilePath($source);

		self::assertEquals('corrupted', file_get_contents($publishedFile));
	}

	public function testAppendTimestampTrue()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setAppendTimestamp(true);
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$publishedUrl = $manager->publishFilePath($fileToPublish);

		self::assertStringContainsString('?v=', $publishedUrl);
		self::assertMatchesRegularExpression('/\?v=\d+$/', $publishedUrl);
	}

	public function testAppendTimestampFalse()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setAppendTimestamp(false);
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$publishedUrl = $manager->publishFilePath($fileToPublish);

		self::assertStringNotContainsString('?v=', $publishedUrl);
	}

	public function testTimestampVar()
	{
		$manager = $this->newAssetManager();

		self::assertEquals('v', $manager->getTimestampVar());

		$manager->setBaseUrl('/');
		$manager->setAppendTimestamp(true);
		$manager->setTimestampVar('t');
		$manager->init(null);

		self::assertEquals('t', $manager->getTimestampVar());

		$manager->setTimestampVar('timestamp');
		self::assertEquals('timestamp', $manager->getTimestampVar());

		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$publishedUrl = $manager->publishFilePath($fileToPublish);

		self::assertStringContainsString('?timestamp=', $publishedUrl);
	}

	public function testHashCallbackCustom()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setHashCallback(function ($path) {
			return 'customhash_' . md5($path);
		});
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$publishedUrl = $manager->publishFilePath($fileToPublish);

		self::assertStringContainsString('customhash_', $publishedUrl);
	}

	public function testHashCallbackWithFile()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setHashCallback(function ($path) {
			return is_file($path) ? 'file_hash' : 'dir_hash';
		});
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$publishedUrl = $manager->publishFilePath($fileToPublish);

		self::assertStringContainsString('dir_hash', $publishedUrl);
	}

	public function testHashCallbackWithDirectory()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setHashCallback(function ($path) {
			return is_file($path) ? 'file_hash' : 'dir_hash';
		});
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);

		self::assertStringContainsString('dir_hash', $publishedUrl);
	}

	public function testBeforeCopyCancelsCopy()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setBeforeCopy(function ($from, $to) {
			return false;
		});
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		self::assertTrue(is_dir($publishedDir));
		self::assertFalse(is_file($publishedDir . '/js/app.js'));
	}

	public function testBeforeCopyAllowsCopy()
	{
		$allowedFiles = [];
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setBeforeCopy(function ($from, $to) use (&$allowedFiles) {
			$allowedFiles[] = basename(dirname($from)) . '/' . basename($from);
			return true;
		});
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$manager->publishFilePath($dirToPublish);

		self::assertNotEmpty($allowedFiles);
	}

	public function testAfterCopyCallback()
	{
		$copiedFiles = [];
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setAfterCopy(function ($from, $to) use (&$copiedFiles) {
			$copiedFiles[] = basename($to);
		});
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$manager->publishFilePath($dirToPublish);

		self::assertNotEmpty($copiedFiles);
	}

	public function testBeforeAndAfterCopy()
	{
		$beforeCalled = false;
		$afterCalled = false;

		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setBeforeCopy(function ($from, $to) use (&$beforeCalled) {
			$beforeCalled = true;
			return true;
		});
		$manager->setAfterCopy(function ($from, $to) use (&$afterCalled) {
			$afterCalled = true;
		});
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$manager->publishFilePath($fileToPublish);

		self::assertTrue($beforeCalled);
		self::assertTrue($afterCalled);
	}

	public function testOnlyPattern()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setOnly(['app.js']);
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/testassets/js/app.js';
		$publishedUrl = $manager->publishFilePath($fileToPublish);
		$publishedFile = self::$assetDir . $publishedUrl;

		self::assertTrue(is_file($publishedFile));
	}

	public function testExceptPattern()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setExcept(['*.css', '*.png']);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		self::assertFalse(is_file($publishedDir . '/css/style.css'));
		self::assertFalse(is_file($publishedDir . '/images/logo.png'));
		self::assertTrue(is_file($publishedDir . '/js/app.js'));
	}

	public function testCaseInsensitivePattern()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setCaseSensitive(false);
		$manager->setOnly(['APP.JS']);
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/testassets/js/app.js';
		$publishedUrl = $manager->publishFilePath($fileToPublish);
		$publishedFile = self::$assetDir . $publishedUrl;

		self::assertTrue(is_file($publishedFile));
	}

	public function testHiddenDirectoriesPublished()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		$hasHiddenFile = file_exists($publishedDir . '/.hidden');
		$hasHiddenDir = is_dir($publishedDir . '/.hiddenDir');

		self::assertTrue($hasHiddenFile);
		self::assertTrue($hasHiddenDir);
	}

	public function testEmptyPatterns()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setOnly(null);
		$manager->setExcept(null);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		self::assertTrue(is_dir($publishedDir));
		self::assertTrue(is_file($publishedDir . '/js/app.js'));
		self::assertTrue(is_file($publishedDir . '/css/style.css'));
	}

	public function testAssetMapSimple()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setAssetMap([
			'jquery.js' => '/js/jquery.min.js'
		]);
		$manager->init(null);

		$resolved = $manager->resolveAsset('jquery.js');
		self::assertEquals('/js/jquery.min.js', $resolved);
	}

	public function testAssetMapWithSourcePath()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setAssetMap([
			'jquery.js' => '/js/jquery.min.js'
		]);
		$manager->init(null);

		$resolved = $manager->resolveAsset('jquery.js', 'js/lib');
		self::assertEquals('/js/jquery.min.js', $resolved);
	}

	public function testAssetMapSuffixMatch()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setAssetMap([
			'app.min.js' => '/js/app.js'
		]);
		$manager->init(null);

		$resolved = $manager->resolveAsset('js/lib/app.min.js', 'js/lib');
		self::assertEquals('/js/app.js', $resolved);
	}

	public function testAssetMapNotFound()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setAssetMap([
			'jquery.js' => '/js/jquery.min.js'
		]);
		$manager->init(null);

		$resolved = $manager->resolveAsset('unknown.js');
		self::assertNull($resolved);
	}

	public function testFileMode()
	{
		if (DIRECTORY_SEPARATOR === '\\') {
			$this->markTestSkipped('File permission modes (chmod) are not honored on Windows NTFS.');
		}
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setFileMode(0644);
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$publishedUrl = $manager->publishFilePath($fileToPublish);
		$publishedFile = self::$assetDir . $publishedUrl;

		clearstatcache();
		$perms = fileperms($publishedFile) & 0777;
		self::assertEquals(0644, $perms);
	}

	public function testDirMode()
	{
		if (DIRECTORY_SEPARATOR === '\\') {
			$this->markTestSkipped('Directory permission modes (chmod) are not honored on Windows NTFS.');
		}
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setDirMode(0755);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		clearstatcache();
		$perms = fileperms($publishedDir) & 0777;
		self::assertEquals(0755, $perms);
	}

	public function testPublishOptionsArray()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/testassets/js/app.js';
		$publishedUrl = $manager->publishFilePath($fileToPublish, [
			'forceCopy' => true,
			'only' => ['app.js'],
			'except' => [],
			'caseSensitive' => true,
			'beforeCopy' => null,
			'afterCopy' => null
		]);
		$publishedFile = self::$assetDir . $publishedUrl;

		self::assertTrue(is_file($publishedFile));
	}

	public function testOnlyPatternNotMatching()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setOnly(['nonexistent.js']);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		self::assertTrue(is_dir($publishedDir));
	}

	public function testOnlyPatternMultiple()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setOnly(['app.js', 'style.css', 'logo.png']);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		self::assertTrue(is_dir($publishedDir));
		self::assertTrue(is_file($publishedDir . '/js/app.js'));
		self::assertTrue(is_file($publishedDir . '/css/style.css'));
		self::assertTrue(is_file($publishedDir . '/images/logo.png'));
	}

	public function testExceptPatternNotMatching()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setExcept(['nonexistent.css']);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		self::assertTrue(is_file($publishedDir . '/css/style.css'));
	}

	public function testExceptPatternMultiple()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setExcept(['*.css', '*.png', '*.js']);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		self::assertFalse(is_file($publishedDir . '/css/style.css'));
		self::assertFalse(is_file($publishedDir . '/images/logo.png'));
		self::assertFalse(is_file($publishedDir . '/js/app.js'));
	}

	public function testOnlyAndExceptCombined()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setOnly(['*.js', '*.css']);
		$manager->setExcept(['app.js']);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		self::assertTrue(is_dir($publishedDir));
	}

	public function testCaseSensitivePattern()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setCaseSensitive(true);
		$manager->setOnly(['app.JS']);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		self::assertTrue(is_dir($publishedDir));
	}

	public function testCaseSensitivePatternMatching()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setCaseSensitive(true);
		$manager->setOnly(['app.js']);
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/testassets/js/app.js';
		$publishedUrl = $manager->publishFilePath($fileToPublish);
		$publishedFile = self::$assetDir . $publishedUrl;

		self::assertTrue(is_file($publishedFile));
	}

	public function testCaseSensitiveExcept()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setCaseSensitive(true);
		$manager->setExcept(['APP.JS']);
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/testassets/js/app.js';
		$publishedUrl = $manager->publishFilePath($fileToPublish);
		$publishedFile = self::$assetDir . $publishedUrl;

		self::assertTrue(is_file($publishedFile));
	}

	public function testResolveAssetWithExactMatch()
	{
		$manager = $this->newAssetManager();
		$manager->setAssetMap([
			'jquery.js' => '/js/jquery.min.js',
			'app.js' => '/js/bundle.js'
		]);
		$manager->init(null);

		$resolved = $manager->resolveAsset('jquery.js');
		self::assertEquals('/js/jquery.min.js', $resolved);

		$resolved = $manager->resolveAsset('app.js');
		self::assertEquals('/js/bundle.js', $resolved);
	}

	public function testResolveAssetNoSourcePath()
	{
		$manager = $this->newAssetManager();
		$manager->setAssetMap([
			'custom.js' => '/dist/custom.min.js'
		]);
		$manager->init(null);

		$resolved = $manager->resolveAsset('custom.js');
		self::assertEquals('/dist/custom.min.js', $resolved);
	}

	public function testResolveAssetWithPrefixMatch()
	{
		$manager = $this->newAssetManager();
		$manager->setAssetMap([
			'jquery.min.js' => '/js/jquery.min.js',
			'*.min.js' => '/dist/min.js'
		]);
		$manager->init(null);

		$resolved = $manager->resolveAsset('jquery.min.js', 'js');
		self::assertEquals('/js/jquery.min.js', $resolved);
	}

	/**
	 * Regression: the suffix-match must be bounded by the length of the
	 * source-qualified asset, not the bare asset. A map key longer than the
	 * bare asset but shorter than "sourcePath/asset" was previously skipped.
	 */
	public function testResolveAssetSuffixMatchKeyLongerThanAsset()
	{
		$manager = $this->newAssetManager();
		$manager->setAssetMap([
			'lib/app.min.js' => '/dist/app.js'
		]);
		$manager->init(null);

		// asset = 'app.min.js' (10), key = 'lib/app.min.js' (14),
		// assetWithSource = 'js/lib/app.min.js' (17): key must still match.
		$resolved = $manager->resolveAsset('app.min.js', 'js/lib');
		self::assertEquals('/dist/app.js', $resolved);
	}

	public function testGetPublished()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$manager->publishFilePath($fileToPublish);

		$published = $manager->getPublished();
		self::assertArrayHasKey($fileToPublish, $published);
	}

	public function testGetPublishedPathNotPublished()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$path = $manager->getPublishedPath('/nonexistent/file.txt');
		self::assertNotNull($path);
		self::assertStringContainsString(self::$assetDir, $path);
	}

	public function testGetPublishedUrlNotPublished()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$url = $manager->getPublishedUrl('/nonexistent/file.txt');
		self::assertNotNull($url);
		self::assertStringStartsWith('/', $url);
	}

	public function testBeforeCopyWithDirectory()
	{
		$calledFiles = [];
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setBeforeCopy(function ($from, $to) use (&$calledFiles) {
			$calledFiles[] = basename($from);
			return true;
		});
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$manager->publishFilePath($dirToPublish);

		self::assertContains('app.js', $calledFiles);
		self::assertContains('style.css', $calledFiles);
	}

	public function testAfterCopyWithDirectory()
	{
		$calledFiles = [];
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setAfterCopy(function ($from, $to) use (&$calledFiles) {
			$calledFiles[] = basename($to);
			return true;
		});
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$manager->publishFilePath($dirToPublish);

		self::assertContains('app.js', $calledFiles);
		self::assertContains('style.css', $calledFiles);
	}

	public function testBeforeCopyReturnsFalseForFile()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setBeforeCopy(function ($from, $to) {
			if (strpos($from, 'app.js') !== false) {
				return false;
			}
			return true;
		});
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		self::assertFalse(is_file($publishedDir . '/js/app.js'));
		self::assertTrue(is_file($publishedDir . '/css/style.css'));
	}

	public function testGetLinkAssetsDefault()
	{
		$manager = $this->newAssetManager();
		self::assertFalse($manager->getLinkAssets());
	}

	public function testGetForceCopyDefault()
	{
		$manager = $this->newAssetManager();
		self::assertFalse($manager->getForceCopy());
	}

	public function testGetAppendTimestampDefault()
	{
		$manager = $this->newAssetManager();
		self::assertFalse($manager->getAppendTimestamp());
	}

	public function testGetHashCallbackDefault()
	{
		$manager = $this->newAssetManager();
		self::assertNull($manager->getHashCallback());
	}

	public function testGetBeforeCopyDefault()
	{
		$manager = $this->newAssetManager();
		self::assertNull($manager->getBeforeCopy());
	}

	public function testGetAfterCopyDefault()
	{
		$manager = $this->newAssetManager();
		self::assertNull($manager->getAfterCopy());
	}

	public function testGetAssetMapDefault()
	{
		$manager = $this->newAssetManager();
		self::assertEquals([], $manager->getAssetMap());
	}

	public function testGetOnlyDefault()
	{
		$manager = $this->newAssetManager();
		self::assertNull($manager->getOnly());
	}

	public function testGetExceptDefault()
	{
		$manager = $this->newAssetManager();
		self::assertNull($manager->getExcept());
	}

	public function testGetCaseSensitiveDefault()
	{
		$manager = $this->newAssetManager();
		self::assertTrue($manager->getCaseSensitive());
	}

	public function testGetFileModeDefault()
	{
		$manager = $this->newAssetManager();
		self::assertNull($manager->getFileMode());
	}

	public function testGetDirModeDefault()
	{
		$manager = $this->newAssetManager();
		$manager->init(null);
		self::assertEquals(Prado::getDefaultDirPermissions(), $manager->getDirMode());
	}

	public function testSetOnlyEmptyArray()
	{
		$manager = $this->newAssetManager();
		$manager->setOnly([]);
		self::assertEquals([], $manager->getOnly());
	}

	public function testSetExceptEmptyArray()
	{
		$manager = $this->newAssetManager();
		$manager->setExcept([]);
		self::assertEquals([], $manager->getExcept());
	}

	public function testPublishFilePathWithOptionsOverridesProperty()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setOnly(['nonexistent.js']);
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/testassets/js/app.js';
		$publishedUrl = $manager->publishFilePath($fileToPublish, [
			'only' => ['app.js']
		]);
		$publishedFile = self::$assetDir . $publishedUrl;

		self::assertTrue(is_file($publishedFile));
	}

	public function testPublishFilePathWithExceptOptionOverridesProperty()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setExcept(['*.js']);
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/testassets/js/app.js';
		$publishedUrl = $manager->publishFilePath($fileToPublish, [
			'except' => []
		]);
		$publishedFile = self::$assetDir . $publishedUrl;

		self::assertTrue(is_file($publishedFile));
	}
	
	public function testPublishFilePathWithCaseSensitiveOptionOverridesProperty()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setCaseSensitive(true);
		$manager->init(null);
	
		$fileToPublish = __DIR__ . '/data/testassets/js/app.js';
		$publishedUrl = $manager->publishFilePath($fileToPublish, [
			'caseSensitive' => true,
			'only' => ['APP.JS']
		]);
		$publishedFile = self::$assetDir . $publishedUrl;
	
		self::assertFalse(is_file($publishedFile));
	}

	public function testPublishFilePathWithCaseInsensitiveOptionOverridesProperty()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setCaseSensitive(true);
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/testassets/js/app.js';
		$publishedUrl = $manager->publishFilePath($fileToPublish, [
			'caseSensitive' => false,
			'only' => ['APP.JS']
		]);
		$publishedFile = self::$assetDir . $publishedUrl;

		self::assertTrue(is_file($publishedFile));
	}

	public function testPublishFilePathWithBeforeCopyOptionOverridesProperty()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setBeforeCopy(function () { return false; });
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/testassets/js/app.js';
		$publishedUrl = $manager->publishFilePath($fileToPublish, [
			'beforeCopy' => function () { return true; }
		]);
		$publishedFile = self::$assetDir . $publishedUrl;

		self::assertTrue(is_file($publishedFile));
	}

	public function testPublishFilePathWithAfterCopyOption()
	{
		$afterCalled = false;
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/testassets/js/app.js';
		$manager->publishFilePath($fileToPublish, [
			'afterCopy' => function () use (&$afterCalled) { $afterCalled = true; }
		]);

		self::assertTrue($afterCalled);
	}

	public function testHiddenFilesIncludedWhenNotExcluded()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		self::assertTrue(file_exists($publishedDir . '/.hidden'));
	}

	public function testMultiplePatternsWithGlob()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setOnly(['*.js', '*.css']);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		self::assertTrue(is_file($publishedDir . '/js/app.js'));
		self::assertTrue(is_file($publishedDir . '/css/style.css'));
		self::assertFalse(is_file($publishedDir . '/images/logo.png'));
	}

	public function testLinkAssetsHashDiffers()
	{
		$this->requireSymlinks();
		$manager1 = $this->newAssetManager();
		$manager1->setBaseUrl('/');
		$manager1->setLinkAssets(false);
		$manager1->init(null);

		$manager2 = $this->newAssetManager();
		$manager2->setBaseUrl('/');
		$manager2->setLinkAssets(true);
		$manager2->init(null);

		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$url1 = $manager1->publishFilePath($fileToPublish);
		$url2 = $manager2->publishFilePath($fileToPublish);

		self::assertNotEquals($url1, $url2);
	}

	public function testCopyDirectoryCreatesDestination()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets/js';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		self::assertTrue(is_dir($publishedDir));
		self::assertTrue(is_file($publishedDir . '/app.js'));
	}

	public function testForceCopyWithDirectoryPublishing()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setForceCopy(true);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $manager->getPublishedUrl($dirToPublish);

		$originalMtime = filemtime($publishedDir . '/js/app.js');

		sleep(2);
		$newTime = time();
		touch($publishedDir . '/js/app.js', $newTime);

		$manager->publishFilePath($dirToPublish);
		$newMtime = filemtime($publishedDir . '/js/app.js');

		self::assertGreaterThanOrEqual($originalMtime, $newMtime);
	}

	public function testTimestampVarWithEmptyString()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setAppendTimestamp(true);
		$manager->setTimestampVar('');
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$publishedUrl = $manager->publishFilePath($fileToPublish);

		self::assertStringContainsString('?=', $publishedUrl);
	}

	/**
	 * The contents of a dot-prefixed directory are published recursively now
	 * that the blanket dotfile exclusion has been removed.
	 */
	public function testHiddenDirectoryContentsPublished()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedDir = self::$assetDir . $manager->publishFilePath($dirToPublish);

		self::assertTrue(is_file($publishedDir . '/.hiddenDir/secret.txt'));
	}

	/**
	 * Files and directories listed in PATH_COPY_EXCEPTIONS remain excluded even
	 * though general dotfiles are now published.
	 */
	public function testPathCopyExceptionsExcluded()
	{
		$src = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tassetmgr_exceptions_' . getmypid();
		$this->removeDirectory($src);
		mkdir($src, Prado::getDefaultDirPermissions(), true);
		file_put_contents($src . DIRECTORY_SEPARATOR . 'keep.js', 'keep');
		foreach (TAssetManager::PATH_COPY_EXCEPTIONS as $exception) {
			mkdir($src . DIRECTORY_SEPARATOR . $exception);
			file_put_contents($src . DIRECTORY_SEPARATOR . $exception . DIRECTORY_SEPARATOR . 'inside.txt', 'secret');
		}

		try {
			$manager = $this->newAssetManager();
			$manager->setBaseUrl('/');
			$manager->init(null);
			$publishedDir = self::$assetDir . $manager->publishFilePath($src);

			self::assertTrue(is_file($publishedDir . DIRECTORY_SEPARATOR . 'keep.js'));
			foreach (TAssetManager::PATH_COPY_EXCEPTIONS as $exception) {
				self::assertFalse(file_exists($publishedDir . DIRECTORY_SEPARATOR . $exception), "$exception must be excluded");
			}
		} finally {
			$this->removeDirectory($src);
		}
	}

	/**
	 * With forceCopy, a stale single-file symlink is unlinked and recreated so
	 * it points back at the source.
	 */
	public function testForceCopyRefreshesSymlink()
	{
		$this->requireSymlinks();
		$source = __DIR__ . '/data/pradoheader.gif';
		$other = __DIR__ . '/data/testassets/js/app.js';

		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setLinkAssets(true);
		$manager->init(null);
		$manager->publishFilePath($source);
		$publishedFile = $manager->getPublishedPath($source);
		self::assertTrue(is_link($publishedFile));

		// Point the published symlink at the wrong target.
		unlink($publishedFile);
		symlink($other, $publishedFile);
		self::assertEquals(realpath($other), realpath($publishedFile));

		$manager2 = $this->newAssetManager();
		$manager2->setBaseUrl('/');
		$manager2->setLinkAssets(true);
		$manager2->setForceCopy(true);
		$manager2->init(null);
		$manager2->publishFilePath($source);

		self::assertEquals(realpath($source), realpath($publishedFile));
	}

	/**
	 * Without forceCopy, an existing single-file symlink is left untouched even
	 * when it points at the wrong target.
	 */
	public function testSymlinkNotRefreshedWithoutForceCopy()
	{
		$this->requireSymlinks();
		$source = __DIR__ . '/data/pradoheader.gif';
		$other = __DIR__ . '/data/testassets/js/app.js';

		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setLinkAssets(true);
		$manager->init(null);
		$manager->publishFilePath($source);
		$publishedFile = $manager->getPublishedPath($source);

		unlink($publishedFile);
		symlink($other, $publishedFile);

		$manager2 = $this->newAssetManager();
		$manager2->setBaseUrl('/');
		$manager2->setLinkAssets(true);
		$manager2->init(null);
		$manager2->publishFilePath($source);

		self::assertEquals(realpath($other), realpath($publishedFile));
	}

	/**
	 * With forceCopy, a stale symlink inside a published directory is unlinked
	 * and recreated to point back at the source file.
	 */
	public function testForceCopyRefreshesSymlinkInDirectory()
	{
		$this->requireSymlinks();
		$dir = __DIR__ . '/data/testassets';
		$other = __DIR__ . '/data/pradoheader.gif';

		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setLinkAssets(true);
		$manager->init(null);
		$manager->publishFilePath($dir);
		$publishedDir = self::$assetDir . $manager->getPublishedUrl($dir);
		$linkedFile = $publishedDir . '/js/app.js';
		self::assertTrue(is_link($linkedFile));

		unlink($linkedFile);
		symlink($other, $linkedFile);
		self::assertEquals(realpath($other), realpath($linkedFile));

		$manager2 = $this->newAssetManager();
		$manager2->setBaseUrl('/');
		$manager2->setLinkAssets(true);
		$manager2->setForceCopy(true);
		$manager2->init(null);
		$manager2->publishFilePath($dir);

		self::assertEquals(realpath($dir . '/js/app.js'), realpath($linkedFile));
	}

	/**
	 * An "only" pattern containing a slash is anchored to the path relative to the
	 * published root. "js/*.js" matches js/app.js but not files in other directories.
	 */
	public function testOnlyAnchoredPathPattern()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setOnly(['js/*.js']);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedDir = self::$assetDir . $manager->publishFilePath($dirToPublish);

		self::assertTrue(is_file($publishedDir . '/js/app.js'));
		self::assertFalse(is_file($publishedDir . '/lib/vendor.js'));
		self::assertFalse(is_file($publishedDir . '/subdir/nested.js'));
	}

	/**
	 * An anchored "except" pattern excludes only the matching path, leaving the
	 * same file name in other directories untouched.
	 */
	public function testExceptAnchoredPathPattern()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setExcept(['lib/vendor.js']);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedDir = self::$assetDir . $manager->publishFilePath($dirToPublish);

		self::assertFalse(is_file($publishedDir . '/lib/vendor.js'));
		self::assertTrue(is_file($publishedDir . '/subdir/nested.js'));
		self::assertTrue(is_file($publishedDir . '/js/app.js'));
	}

	/**
	 * An "except" pattern prunes a whole sub-directory by name; its contents are
	 * never reached.
	 */
	public function testExceptPrunesDirectory()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setExcept(['subdir']);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedDir = self::$assetDir . $manager->publishFilePath($dirToPublish);

		self::assertFalse(file_exists($publishedDir . '/subdir'));
		self::assertFalse(is_file($publishedDir . '/subdir/nested.js'));
		self::assertTrue(is_file($publishedDir . '/js/app.js'));
	}

	/**
	 * A sub-directory whose entire contents are filtered out is removed rather than
	 * left behind as an empty directory.
	 */
	public function testFilteredEmptyDirectoryIsPruned()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setExcept(['*.css']);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedDir = self::$assetDir . $manager->publishFilePath($dirToPublish);

		// css/ held only style.css, which was excluded, so the directory is gone.
		self::assertFalse(file_exists($publishedDir . '/css'));
		self::assertTrue(is_file($publishedDir . '/js/app.js'));
	}

	/**
	 * A directory that is empty in the source is preserved, since nothing was
	 * filtered out of it.
	 */
	public function testGenuineEmptySourceDirectoryPreserved()
	{
		$src = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tassetmgr_empty_' . getmypid();
		$this->removeDirectory($src);
		mkdir($src . DIRECTORY_SEPARATOR . 'emptydir', Prado::getDefaultDirPermissions(), true);
		file_put_contents($src . DIRECTORY_SEPARATOR . 'keep.js', 'keep');

		try {
			$manager = $this->newAssetManager();
			$manager->setBaseUrl('/');
			$manager->setExcept(['*.css']);
			$manager->init(null);
			$publishedDir = self::$assetDir . $manager->publishFilePath($src);

			self::assertTrue(is_dir($publishedDir . DIRECTORY_SEPARATOR . 'emptydir'));
			self::assertTrue(is_file($publishedDir . DIRECTORY_SEPARATOR . 'keep.js'));
		} finally {
			$this->removeDirectory($src);
		}
	}

	/**
	 * An "only" pattern filters files but never prunes a directory, so matching
	 * files nested in sub-directories remain reachable.
	 */
	public function testOnlyDoesNotPruneNestedFiles()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setOnly(['*.js']);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedDir = self::$assetDir . $manager->publishFilePath($dirToPublish);

		self::assertTrue(is_file($publishedDir . '/js/app.js'));
		self::assertTrue(is_file($publishedDir . '/lib/vendor.js'));
		self::assertTrue(is_file($publishedDir . '/subdir/nested.js'));
		self::assertFalse(is_file($publishedDir . '/css/style.css'));
	}

	/**
	 * Publishing the same path twice with different options re-publishes rather than
	 * returning the first call's cached URL, so the second option set takes effect.
	 */
	public function testPublishOptionsAffectCache()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$source = __DIR__ . '/data/pradoheader.gif';
		$publishedFile = $manager->getPublishedPath($source);

		// First publish filters the file out; nothing is copied but the URL caches.
		$manager->publishFilePath($source, ['only' => ['nomatch.gif']]);
		self::assertFalse(is_file($publishedFile));

		// A different option set must not return the cached URL; the file now copies.
		$manager->publishFilePath($source, ['only' => ['*.gif']]);
		self::assertTrue(is_file($publishedFile));
	}

	/**
	 * Identical no-option publishes still hit the cache: the second call returns the
	 * first call's URL unchanged.
	 */
	public function testPublishWithoutOptionsUsesCache()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$source = __DIR__ . '/data/pradoheader.gif';
		$first = $manager->publishFilePath($source);
		$second = $manager->publishFilePath($source);

		self::assertEquals($first, $second);
	}

	/**
	 * Unlike a whole-directory symlink, Prado links files individually and applies
	 * the "except" filter in link mode, so an excluded file is neither linked nor
	 * copied into the published directory.
	 */
	public function testLinkAssetsAppliesExceptFilter()
	{
		$this->requireSymlinks();
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setLinkAssets(true);
		$manager->setExcept(['*.css']);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedDir = self::$assetDir . $manager->publishFilePath($dirToPublish);

		self::assertFalse(file_exists($publishedDir . '/css/style.css'));
		self::assertTrue(is_link($publishedDir . '/js/app.js'));
	}

	/**
	 * A map key is matched as a suffix only at a path boundary. The key "app.js"
	 * resolves "lib/app.js" but not "myapp.js".
	 */
	public function testResolveAssetSuffixRequiresPathBoundary()
	{
		$manager = $this->newAssetManager();
		$manager->setAssetMap(['app.js' => '/mapped/app.js']);
		$manager->init(null);

		self::assertEquals('/mapped/app.js', $manager->resolveAsset('lib/app.js'));
		self::assertEquals('/mapped/app.js', $manager->resolveAsset('app.js'));
		self::assertNull($manager->resolveAsset('myapp.js'));
	}

	/**
	 * The checksum file of a tar publish is copied regardless of an instance "only"
	 * filter that would otherwise exclude it, so the tar still deploys.
	 */
	public function testPublishTarFileIgnoresOnlyExcept()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setOnly(['*.js']);
		$manager->setExcept(['*.md5']);
		$manager->init(null);

		$tarFile = __DIR__ . '/data/aTarFile.tar';
		$md5File = __DIR__ . '/data/aTarFile.md5';

		$publishedDir = self::$assetDir . $manager->publishTarFile($tarFile, $md5File);

		self::assertTrue(is_file($publishedDir . '/aTarFile.md5'));
		self::assertTrue(is_file($publishedDir . '/pradoheader.gif'));
	}

	/**
	 * A single file filtered out by "only" is not copied and leaves no empty
	 * destination directory behind.
	 */
	public function testFilteredSingleFileLeavesNoEmptyDirectory()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$source = __DIR__ . '/data/pradoheader.gif';
		$manager->publishFilePath($source, ['only' => ['nomatch.gif']]);

		$destDir = dirname($manager->getPublishedPath($source));
		self::assertFalse(is_dir($destDir));
	}

	/**
	 * A "caseSensitive" anchored path pattern respects case; switching it off makes
	 * the same pattern match.
	 */
	public function testCaseSensitiveAnchoredPattern()
	{
		$dirToPublish = __DIR__ . '/data/testassets';

		$sensitive = $this->newAssetManager();
		$sensitive->setBaseUrl('/');
		$sensitive->setCaseSensitive(true);
		$sensitive->setOnly(['js/APP.JS']);
		$sensitive->init(null);
		$sensitiveDir = self::$assetDir . $sensitive->publishFilePath($dirToPublish);
		self::assertFalse(is_file($sensitiveDir . '/js/app.js'));

		$insensitive = $this->newAssetManager();
		$insensitive->setBaseUrl('/');
		$insensitive->setCaseSensitive(false);
		$insensitive->setOnly(['js/APP.JS']);
		$insensitive->init(null);
		$insensitiveDir = self::$assetDir . $insensitive->publishFilePath($dirToPublish);
		self::assertTrue(is_file($insensitiveDir . '/js/app.js'));
	}

	/**
	 * A directory symlink that points back at an ancestor does not loop forever;
	 * each real directory is entered once and the non-cyclic content publishes.
	 */
	public function testSymlinkCycleDoesNotRecurseInfinitely()
	{
		$this->requireSymlinks();
		$base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tassetmgr_cycle_' . getmypid();
		$this->removeDirectory($base);
		mkdir($base . DIRECTORY_SEPARATOR . 'sub', Prado::getDefaultDirPermissions(), true);
		file_put_contents($base . DIRECTORY_SEPARATOR . 'a.js', 'a');
		file_put_contents($base . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'b.js', 'b');
		// A symlink inside sub/ pointing back at the publish root forms a cycle.
		symlink($base, $base . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'loop');

		try {
			$manager = $this->newAssetManager();
			$manager->setBaseUrl('/');
			$manager->init(null);
			$publishedDir = self::$assetDir . $manager->publishFilePath($base);

			self::assertTrue(is_file($publishedDir . '/a.js'));
			self::assertTrue(is_file($publishedDir . '/sub/b.js'));
			// The cycle target is entered once, so it is not recreated in the output.
			self::assertFalse(file_exists($publishedDir . '/sub/loop'));
		} finally {
			// Remove the cycle link before recursive cleanup follows it.
			@unlink($base . DIRECTORY_SEPARATOR . 'sub' . DIRECTORY_SEPARATOR . 'loop');
			$this->removeDirectory($base);
		}
	}

	/**
	 * getPublished reflects a list installed through the protected setPublished,
	 * which page-state restoration uses to seed already-published assets.
	 */
	public function testSetPublishedSeedsPublishedList()
	{
		$manager = new class () extends TAssetManager {
			public function exposeSetPublished($values)
			{
				$this->setPublished($values);
			}
		};
		$manager->exposeSetPublished(['/src/path' => '/assets/abc/file.js']);

		self::assertEquals(['/src/path' => '/assets/abc/file.js'], $manager->getPublished());
	}

	/**
	 * A beforeCopy that returns false cancels publishing of a single file.
	 */
	public function testBeforeCopyCancelsSingleFile()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$source = __DIR__ . '/data/pradoheader.gif';
		$manager->publishFilePath($source, ['beforeCopy' => function ($src, $dst) {
			return false;
		}]);

		self::assertFalse(is_file($manager->getPublishedPath($source)));
	}

	/**
	 * fileMode is applied to files copied during a directory publish.
	 */
	public function testFileModeOnDirectoryCopy()
	{
		$this->requireFileModes();
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setFileMode(0644);
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedDir = self::$assetDir . $manager->publishFilePath($dirToPublish);

		self::assertEquals(0644, fileperms($publishedDir . '/js/app.js') & 0777);
	}

	/**
	 * A second publish of the same tar returns the cached URL.
	 */
	public function testPublishTarFileReturnsCachedUrl()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$tarFile = __DIR__ . '/data/aTarFile.tar';
		$md5File = __DIR__ . '/data/aTarFile.md5';

		$first = $manager->publishTarFile($tarFile, $md5File);
		$second = $manager->publishTarFile($tarFile, $md5File);

		self::assertEquals($first, $second);
	}

	/**
	 * publishTarFile with a valid checksum but an invalid tar file throws.
	 */
	public function testPublishTarFileInvalidTarThrows()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$md5File = __DIR__ . '/data/aTarFile.md5';

		$this->expectException(TIOException::class);
		$manager->publishTarFile(__DIR__ . '/data/does_not_exist.tar', $md5File);
	}

	/**
	 * copyDirectory throws when the source directory cannot be opened.
	 */
	public function testCopyDirectoryInvalidSourceThrows()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$this->expectException(TInvalidDataValueException::class);
		$manager->copyDirectory(__DIR__ . '/data/no_such_directory', self::$assetDir . '/dst');
	}

	/**
	 * When symlinking a single file fails and leaves no file or link, copyFile
	 * rethrows the failure.
	 */
	public function testCopyFileSymlinkFailureRethrows()
	{
		$manager = new class () extends TAssetManager {
			protected function symlink($target, $link)
			{
				throw new \RuntimeException('symlink failed');
			}
		};
		$manager->setBaseUrl('/');
		$manager->setLinkAssets(true);
		$manager->init(null);

		$this->expectException(\RuntimeException::class);
		$manager->publishFilePath(__DIR__ . '/data/pradoheader.gif');
	}

	/**
	 * When symlinking a file inside a directory publish fails and leaves no file
	 * or link, copyDirectory rethrows the failure.
	 */
	public function testCopyDirectorySymlinkFailureRethrows()
	{
		$manager = new class () extends TAssetManager {
			protected function symlink($target, $link)
			{
				throw new \RuntimeException('symlink failed');
			}
		};
		$manager->setBaseUrl('/');
		$manager->setLinkAssets(true);
		$manager->init(null);

		$this->expectException(\RuntimeException::class);
		$manager->publishFilePath(__DIR__ . '/data/testassets');
	}

	/**
	 * A file literally named "0" does not prematurely end directory iteration;
	 * it and any files after it are still published.
	 */
	public function testZeroNamedFilePublished()
	{
		$base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tassetmgr_zero_' . getmypid();
		$this->removeDirectory($base);
		mkdir($base, Prado::getDefaultDirPermissions(), true);
		file_put_contents($base . DIRECTORY_SEPARATOR . '0', 'zero');
		file_put_contents($base . DIRECTORY_SEPARATOR . 'after.js', 'after');

		try {
			$manager = $this->newAssetManager();
			$manager->setBaseUrl('/');
			$manager->init(null);
			$publishedDir = self::$assetDir . $manager->publishFilePath($base);

			self::assertTrue(is_file($publishedDir . DIRECTORY_SEPARATOR . '0'));
			self::assertTrue(is_file($publishedDir . DIRECTORY_SEPARATOR . 'after.js'));
		} finally {
			$this->removeDirectory($base);
		}
	}

	/**
	 * In Performance mode an instance forceCopy still re-copies an existing single
	 * file. Without it the publish is skipped because the destination already exists.
	 */
	public function testSingleFileForceCopyInstancePropertyInPerformanceMode()
	{
		$source = __DIR__ . '/data/pradoheader.gif';
		$previousMode = self::$app->getMode();

		try {
			$manager1 = $this->newAssetManager();
			$manager1->setBaseUrl('/');
			$manager1->init(null);
			$manager1->publishFilePath($source);
			$publishedFile = $manager1->getPublishedPath($source);

			file_put_contents($publishedFile, 'corrupted');
			touch($publishedFile, time() + 10);

			self::$app->setMode('Performance');
			$manager2 = $this->newAssetManager();
			$manager2->setBaseUrl('/');
			$manager2->setForceCopy(true);
			$manager2->init(null);
			$manager2->publishFilePath($source);

			self::assertEquals(file_get_contents($source), file_get_contents($publishedFile));
		} finally {
			self::$app->setMode($previousMode);
		}
	}

	/**
	 * A published symlink stores a relative target yet still resolves to the source.
	 */
	public function testLinkAssetsAreRelative()
	{
		$this->requireSymlinks();
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setLinkAssets(true);
		$manager->init(null);

		$source = __DIR__ . '/data/pradoheader.gif';
		$manager->publishFilePath($source);
		$publishedFile = $manager->getPublishedPath($source);

		self::assertTrue(is_link($publishedFile));
		$target = readlink($publishedFile);
		self::assertNotEquals(DIRECTORY_SEPARATOR, substr($target, 0, 1));
		self::assertStringStartsWith('..', $target);
		self::assertEquals(realpath($source), realpath($publishedFile));
	}

	/**
	 * validateSymlinks reports an intact link as valid (true) and leaves it in place.
	 */
	public function testValidateSymlinksIntactLink()
	{
		$this->requireSymlinks();
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setLinkAssets(true);
		$manager->init(null);

		$source = __DIR__ . '/data/pradoheader.gif';
		$manager->publishFilePath($source);
		$publishedFile = $manager->getPublishedPath($source);

		self::assertTrue($manager->validateSymlinks($publishedFile, true));
		self::assertTrue(is_link($publishedFile));
	}

	/**
	 * A path that is neither a link nor a directory yields null and is not touched.
	 */
	public function testValidateSymlinksNonLinkFile()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$source = __DIR__ . '/data/pradoheader.gif';
		$manager->publishFilePath($source);
		$publishedFile = $manager->getPublishedPath($source);

		self::assertNull($manager->validateSymlinks($publishedFile, true));
		self::assertTrue(is_file($publishedFile));
	}

	/**
	 * A broken link returns false; it is kept without remove and deleted with it.
	 */
	public function testValidateSymlinksBrokenLink()
	{
		$this->requireSymlinks();
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		// Link to a real target, then delete the target to break it (portable:
		// some platforms cannot create a symlink to a missing target).
		$target = self::$assetDir . DIRECTORY_SEPARATOR . 'broken_target';
		$link = self::$assetDir . DIRECTORY_SEPARATOR . 'broken_link';
		file_put_contents($target, 'x');
		symlink('broken_target', $link);
		unlink($target);

		self::assertFalse($manager->validateSymlinks($link, false));
		self::assertTrue(is_link($link));

		self::assertFalse($manager->validateSymlinks($link, true));
		self::assertFalse(is_link($link));
	}

	/**
	 * validateSymlinks sweeps the hierarchy, removing broken links and keeping intact
	 * ones, and returns the count of broken links found.
	 */
	public function testValidateSymlinksSweepsHierarchy()
	{
		$this->requireSymlinks();
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->setLinkAssets(true);
		$manager->init(null);

		// An intact published link in its own hash directory.
		$source = __DIR__ . '/data/pradoheader.gif';
		$manager->publishFilePath($source);
		$intact = $manager->getPublishedPath($source);

		// Two broken links, one nested: link to real targets, then delete them.
		$goneTop = self::$assetDir . DIRECTORY_SEPARATOR . 'gone1';
		$brokenTop = self::$assetDir . DIRECTORY_SEPARATOR . 'broken_top';
		file_put_contents($goneTop, 'x');
		symlink('gone1', $brokenTop);
		unlink($goneTop);
		$nestedDir = self::$assetDir . DIRECTORY_SEPARATOR . 'nested';
		mkdir($nestedDir, Prado::getDefaultDirPermissions());
		$goneNested = $nestedDir . DIRECTORY_SEPARATOR . 'gone2';
		$brokenNested = $nestedDir . DIRECTORY_SEPARATOR . 'broken_nested';
		file_put_contents($goneNested, 'x');
		symlink('gone2', $brokenNested);
		unlink($goneNested);

		$broken = $manager->validateSymlinks();

		self::assertEquals(2, $broken);
		self::assertFalse(is_link($brokenTop));
		self::assertFalse(is_link($brokenNested));
		self::assertTrue(is_link($intact));
	}

	/**
	 * validateSymlinks with remove disabled counts broken links without deleting them.
	 */
	public function testValidateSymlinksCountOnly()
	{
		$this->requireSymlinks();
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		// Link to a real target, then delete it to break the link (portable).
		$gone = self::$assetDir . DIRECTORY_SEPARATOR . 'gone';
		$broken = self::$assetDir . DIRECTORY_SEPARATOR . 'broken_count';
		file_put_contents($gone, 'x');
		symlink('gone', $broken);
		unlink($gone);

		self::assertEquals(1, $manager->validateSymlinks(null, false));
		self::assertTrue(is_link($broken));
	}

	/**
	 * When the target and link share no common root, the absolute target is kept
	 * because no relative link can express the path.
	 */
	public function testRelativeSymlinkTargetDifferentRoots()
	{
		$manager = new class () extends TAssetManager {
			public function exposeRelative($target, $link)
			{
				return $this->relativeSymlinkTarget($target, $link);
			}
		};

		// A link path with no leading separator has a first segment that cannot
		// match the absolute target's empty first segment.
		self::assertEquals('/a/b.js', $manager->exposeRelative('/a/b.js', 'rel/dir/c.js'));
	}

	/**
	 * validateSymlinks returns null when handed a path that is neither link nor dir.
	 */
	public function testValidateSymlinksOnNonDirectory()
	{
		$manager = $this->newAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		self::assertNull($manager->validateSymlinks(__DIR__ . '/data/pradoheader.gif', false));
	}
}
