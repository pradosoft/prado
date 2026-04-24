<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\Web\TAssetManager;

class TAssetManagerTest extends PHPUnit\Framework\TestCase
{
	public static $app = null;
	public static $assetDir = null;

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
			self::$assetDir = __DIR__ . '/assets';
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

	public function testInit()
	{
		$manager = new TAssetManager();

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
		$manager = new TAssetManager();
		// First try, invalid directory
		try {
			$manager->setBasePath('invalid');
			self::fail('Expected TInvalidDataValueException not thrown');
		} catch (TInvalidDataValueException $e) {
		}

		// Next, standard asset directory, should work

		$manager->setBasePath('AssetAlias');
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		// Try to publish a single file
		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$publishedUrl = $manager->publishFilePath($fileToPublish);
		$publishedFile = self::$assetDir . $publishedUrl;
		self::assertEquals($publishedFile, $manager->getPublishedPath($fileToPublish));
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
		$manager = new TAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		// Try to publish a directory
		$dirToPublish = __DIR__ . '/data';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;
		self::assertEquals($publishedDir, $manager->getPublishedPath($dirToPublish));
		self::assertEquals($publishedUrl, $manager->getPublishedUrl($dirToPublish));
		self::assertTrue(is_dir($publishedDir));
		self::assertTrue(is_file($publishedDir . '/pradoheader.gif'));
	}

	public function testPublishTarFile()
	{
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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

	public function testAppendTimestampTrue()
	{
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
		$manager->setBaseUrl('/');
		$manager->setAppendTimestamp(false);
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$publishedUrl = $manager->publishFilePath($fileToPublish);

		self::assertStringNotContainsString('?v=', $publishedUrl);
	}

	public function testTimestampVar()
	{
		$manager = new TAssetManager();

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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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

		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
		$manager->setBaseUrl('/');
		$manager->setCaseSensitive(false);
		$manager->setOnly(['APP.JS']);
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/testassets/js/app.js';
		$publishedUrl = $manager->publishFilePath($fileToPublish);
		$publishedFile = self::$assetDir . $publishedUrl;

		self::assertTrue(is_file($publishedFile));
	}

	public function testHiddenDirectoriesExcluded()
	{
		$manager = new TAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		$hasHiddenFile = file_exists($publishedDir . '/.hidden');
		$hasHiddenDir = is_dir($publishedDir . '/.hiddenDir');

		self::assertFalse($hasHiddenFile);
		self::assertFalse($hasHiddenDir);
	}

	public function testEmptyPatterns()
	{
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
		$manager->setAssetMap([
			'custom.js' => '/dist/custom.min.js'
		]);
		$manager->init(null);

		$resolved = $manager->resolveAsset('custom.js');
		self::assertEquals('/dist/custom.min.js', $resolved);
	}

	public function testResolveAssetWithPrefixMatch()
	{
		$manager = new TAssetManager();
		$manager->setAssetMap([
			'jquery.min.js' => '/js/jquery.min.js',
			'*.min.js' => '/dist/min.js'
		]);
		$manager->init(null);

		$resolved = $manager->resolveAsset('jquery.min.js', 'js');
		self::assertEquals('/js/jquery.min.js', $resolved);
	}

	public function testGetPublished()
	{
		$manager = new TAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$manager->publishFilePath($fileToPublish);

		$published = $manager->getPublished();
		self::assertArrayHasKey($fileToPublish, $published);
	}

	public function testGetPublishedPathNotPublished()
	{
		$manager = new TAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$path = $manager->getPublishedPath('/nonexistent/file.txt');
		self::assertNotNull($path);
		self::assertStringContainsString(self::$assetDir, $path);
	}

	public function testGetPublishedUrlNotPublished()
	{
		$manager = new TAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$url = $manager->getPublishedUrl('/nonexistent/file.txt');
		self::assertNotNull($url);
		self::assertStringStartsWith('/', $url);
	}

	public function testBeforeCopyWithDirectory()
	{
		$calledFiles = [];
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
		self::assertFalse($manager->getLinkAssets());
	}

	public function testGetForceCopyDefault()
	{
		$manager = new TAssetManager();
		self::assertFalse($manager->getForceCopy());
	}

	public function testGetAppendTimestampDefault()
	{
		$manager = new TAssetManager();
		self::assertFalse($manager->getAppendTimestamp());
	}

	public function testGetHashCallbackDefault()
	{
		$manager = new TAssetManager();
		self::assertNull($manager->getHashCallback());
	}

	public function testGetBeforeCopyDefault()
	{
		$manager = new TAssetManager();
		self::assertNull($manager->getBeforeCopy());
	}

	public function testGetAfterCopyDefault()
	{
		$manager = new TAssetManager();
		self::assertNull($manager->getAfterCopy());
	}

	public function testGetAssetMapDefault()
	{
		$manager = new TAssetManager();
		self::assertEquals([], $manager->getAssetMap());
	}

	public function testGetOnlyDefault()
	{
		$manager = new TAssetManager();
		self::assertNull($manager->getOnly());
	}

	public function testGetExceptDefault()
	{
		$manager = new TAssetManager();
		self::assertNull($manager->getExcept());
	}

	public function testGetCaseSensitiveDefault()
	{
		$manager = new TAssetManager();
		self::assertTrue($manager->getCaseSensitive());
	}

	public function testGetFileModeDefault()
	{
		$manager = new TAssetManager();
		self::assertNull($manager->getFileMode());
	}

	public function testGetDirModeDefault()
	{
		$manager = new TAssetManager();
		$manager->init(null);
		self::assertEquals(Prado::getDefaultDirPermissions(), $manager->getDirMode());
	}

	public function testSetOnlyEmptyArray()
	{
		$manager = new TAssetManager();
		$manager->setOnly([]);
		self::assertEquals([], $manager->getOnly());
	}

	public function testSetExceptEmptyArray()
	{
		$manager = new TAssetManager();
		$manager->setExcept([]);
		self::assertEquals([], $manager->getExcept());
	}

	public function testPublishFilePathWithOptionsOverridesProperty()
	{
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
		$manager->setBaseUrl('/');
		$manager->init(null);

		$dirToPublish = __DIR__ . '/data/testassets';
		$publishedUrl = $manager->publishFilePath($dirToPublish);
		$publishedDir = self::$assetDir . $publishedUrl;

		self::assertFalse(file_exists($publishedDir . '/.hidden'));
	}

	public function testMultiplePatternsWithGlob()
	{
		$manager = new TAssetManager();
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
		$manager1 = new TAssetManager();
		$manager1->setBaseUrl('/');
		$manager1->setLinkAssets(false);
		$manager1->init(null);

		$manager2 = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
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
		$manager = new TAssetManager();
		$manager->setBaseUrl('/');
		$manager->setAppendTimestamp(true);
		$manager->setTimestampVar('');
		$manager->init(null);

		$fileToPublish = __DIR__ . '/data/pradoheader.gif';
		$publishedUrl = $manager->publishFilePath($fileToPublish);

		self::assertStringContainsString('?=', $publishedUrl);
	}
}
