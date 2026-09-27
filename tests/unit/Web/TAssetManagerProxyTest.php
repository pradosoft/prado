<?php

/**
 * TAssetManagerProxyTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Web;

use Prado\Collections\TWeakCallableCollection;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Exceptions\TUnknownMethodException;
use Prado\IModuleDependency;
use Prado\IProxy;
use Prado\Prado;
use Prado\TApplication;
use Prado\TEventParameter;
use Prado\TModule;
use Prado\Util\Log\TLogger;
use Prado\Web\TAssetManager;
use Prado\Web\TAssetManagerProxy;

// ── Helper classes ─────────────────────────────────────────────────────────────

/**
 * A TAssetManager with an extra property, method, and event to exercise the
 * trait dispatch and event forwarding through the proxy.
 */
class TAssetManagerProxyBackend extends TAssetManager
{
	/** @var ?string used to exercise __get/__set/__isset/__unset forwarding */
	private ?string $_customProp = null;

	public function getCustomProp(): ?string
	{
		return $this->_customProp;
	}

	public function setCustomProp(?string $value): void
	{
		$this->_customProp = $value;
	}

	public function customMethod(string $arg): string
	{
		return 'custom:' . $arg;
	}

	public function onTestEvent(TEventParameter $param): void
	{
		$this->raiseEvent('OnTestEvent', $this, $param);
	}
}

/**
 * A TModule that is NOT a TAssetManager, used to test the invalid-type guard.
 */
class TAssetManagerProxyNotAnAssetManagerModule extends TModule
{
}

/**
 * Exposes protected internals for direct testing.
 */
class TAssetManagerProxyAccessor extends TAssetManagerProxy
{
	public function pubGetZappableSleepProps(array &$exprops): void
	{
		$this->_getZappableSleepProps($exprops);
	}

	public function pubGetAssetManagerDirect(): ?TAssetManager
	{
		return $this->getAssetManagerDirect();
	}
}

// ── Test class ─────────────────────────────────────────────────────────────────

/**
 * TAssetManagerProxyTest class.
 *
 * Tests TAssetManagerProxy: BackingAssetManagerId property, lazy resolution,
 * init() validation and application registration, IModuleDependency, transparent
 * TAssetManager delegation, change logging, cloning, serialization, and events.
 */
class TAssetManagerProxyTest extends \PHPUnit\Framework\TestCase
{
	private ?TApplication $app = null;

	private TAssetManagerProxyBackend $backend;

	private TAssetManagerProxyAccessor $proxy;

	/** @var string the temporary publishing directory of the backend */
	private string $assetDir;

	/** @var string the temporary source directory of files to publish */
	private string $sourceDir;

	protected function setUp(): void
	{
		$this->app = new TApplication(__DIR__ . '/../Caching/mockapp');

		$base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado_amproxy_' . uniqid('', true);
		$this->assetDir = $base . DIRECTORY_SEPARATOR . 'assets';
		$this->sourceDir = $base . DIRECTORY_SEPARATOR . 'source';
		mkdir($this->assetDir, 0o777, true);
		mkdir($this->sourceDir, 0o777, true);
		$this->assetDir = realpath($this->assetDir);
		$this->sourceDir = realpath($this->sourceDir);

		$this->backend = $this->newBackend($this->assetDir, '/assets');
		$this->app->setModule('backingAssets', $this->backend);

		// Build the proxy but do NOT call init(); tests that need it call it.
		$this->proxy = new TAssetManagerProxyAccessor();
		$this->proxy->setBackingAssetManagerId('backingAssets');
	}

	protected function tearDown(): void
	{
		$this->proxy->unlisten();
		$this->backend->unlisten();
		$this->app->unlisten();
		$this->app = null;
		$this->removeDirectory(dirname($this->assetDir));
	}

	// ── Construction / instance ──────────────────────────────────────────────────

	public function testIsInstanceOfTAssetManagerProxy(): void
	{
		$this->assertInstanceOf(TAssetManagerProxy::class, $this->proxy);
	}

	public function testExtendsTAssetManager(): void
	{
		$this->assertInstanceOf(TAssetManager::class, $this->proxy);
	}

	public function testImplementsIModuleDependency(): void
	{
		$this->assertInstanceOf(IModuleDependency::class, $this->proxy);
	}

	public function testImplementsIProxy(): void
	{
		$this->assertInstanceOf(IProxy::class, $this->proxy);
	}

	// ── BackingAssetManagerId ────────────────────────────────────────────────────

	public function testDefaultBackingAssetManagerIdIsEmptyString(): void
	{
		$fresh = new TAssetManagerProxy();
		$this->assertSame('', $fresh->getBackingAssetManagerId());
	}

	public function testSetGetBackingAssetManagerId(): void
	{
		$proxy = new TAssetManagerProxy();
		$proxy->setBackingAssetManagerId('myAssets');
		$this->assertSame('myAssets', $proxy->getBackingAssetManagerId());
	}

	public function testSetBackingAssetManagerIdSameValueIsNoOp(): void
	{
		$before = $this->countLogs(TLogger::WARNING, 'prado.web');
		$this->proxy->setBackingAssetManagerId('backingAssets');
		$this->assertSame('backingAssets', $this->proxy->getBackingAssetManagerId());
		$this->assertSame($before, $this->countLogs(TLogger::WARNING, 'prado.web'));
	}

	public function testSetBackingAssetManagerIdFromEmptyDoesNotLog(): void
	{
		$before = $this->countLogs(TLogger::WARNING, 'prado.web');
		$proxy = new TAssetManagerProxy();
		$proxy->setBackingAssetManagerId('backingAssets');
		$this->assertSame($before, $this->countLogs(TLogger::WARNING, 'prado.web'));
	}

	public function testSetBackingAssetManagerIdChangeLogsWarning(): void
	{
		$before = $this->countLogs(TLogger::WARNING, 'prado.web');
		$this->proxy->setBackingAssetManagerId('otherAssets');
		$this->assertSame($before + 1, $this->countLogs(TLogger::WARNING, 'prado.web'));
	}

	public function testSetBackingAssetManagerIdChangeLogMessageContainsBothIds(): void
	{
		$this->proxy->setBackingAssetManagerId('replacementAssets');
		$logs = Prado::getLogger()->getLogs(TLogger::WARNING, 'prado.web');
		$msg = end($logs)[TLogger::LOG_MESSAGE];
		$this->assertStringContainsString('backingAssets', $msg);
		$this->assertStringContainsString('replacementAssets', $msg);
	}

	public function testMultipleBackingAssetManagerIdChangesEachLog(): void
	{
		$before = $this->countLogs(TLogger::WARNING, 'prado.web');
		$this->proxy->setBackingAssetManagerId('first');
		$this->proxy->setBackingAssetManagerId('second');
		$this->assertSame($before + 2, $this->countLogs(TLogger::WARNING, 'prado.web'));
	}

	public function testSetBackingAssetManagerIdInvalidatesResolvedReference(): void
	{
		$this->proxy->init(null);
		$first = $this->proxy->getAssetManager();

		$secondDir = dirname($this->assetDir) . DIRECTORY_SEPARATOR . 'assets2';
		mkdir($secondDir, 0o777, true);
		$second = $this->newBackend(realpath($secondDir), '/assets2');
		$this->app->setModule('secondAssets', $second);

		$this->proxy->setBackingAssetManagerId('secondAssets');
		$resolved = $this->proxy->getAssetManager();

		$this->assertNotSame($first, $resolved);
		$this->assertSame($second, $resolved);
		$second->unlisten();
	}

	// ── getModuleDependencies ────────────────────────────────────────────────────

	public function testGetModuleDependenciesReturnsNullWhenIdEmpty(): void
	{
		$proxy = new TAssetManagerProxy();
		$this->assertNull($proxy->getModuleDependencies());
		$this->assertNull($proxy->getModuleDependencies(true));
	}

	public function testGetModuleDependenciesReturnsBackingId(): void
	{
		$this->assertSame('backingAssets', $this->proxy->getModuleDependencies());
		$this->assertSame('backingAssets', $this->proxy->getModuleDependencies(true));
	}

	// ── init() ──────────────────────────────────────────────────────────────────

	public function testInitThrowsWhenBackingAssetManagerIdIsEmpty(): void
	{
		$proxy = new TAssetManagerProxy();
		try {
			$proxy->init(null);
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('assetmanagerproxy_backing_asset_manager_id_required', $e->getErrorCode());
		}
	}

	public function testInitRegistersProxyAsApplicationAssetManager(): void
	{
		$this->proxy->init(null);
		$this->assertSame($this->proxy, $this->app->getAssetManager());
	}

	public function testInitMarksInitialized(): void
	{
		$this->assertFalse($this->proxy->getIsInitialized());
		$this->proxy->init(null);
		$this->assertTrue($this->proxy->getIsInitialized());
	}

	public function testInitDoesNotResolveBacking(): void
	{
		$this->proxy->init(null);
		$this->assertNull($this->proxy->pubGetAssetManagerDirect());
	}

	public function testInitDoesNotCreateOwnAssetDirectory(): void
	{
		$entries = scandir($this->assetDir);
		$this->proxy->init(null);
		$this->assertSame($entries, scandir($this->assetDir));
		$this->assertSame($this->assetDir, $this->proxy->getBasePath());
	}

	public function testInitRaisesDyInitOnBehaviors(): void
	{
		$behavior = new TAssetManagerProxyInitBehavior();
		$this->proxy->attachBehavior('initSpy', $behavior);
		$this->proxy->init(['x' => 1]);
		$this->assertSame(1, $behavior->initCalls);
		$this->assertSame(['x' => 1], $behavior->lastConfig);
	}

	// ── getAssetManager() ───────────────────────────────────────────────────────

	public function testGetAssetManagerReturnsBackingModule(): void
	{
		$this->assertSame($this->backend, $this->proxy->getAssetManager());
	}

	public function testGetAssetManagerCachesResolvedReference(): void
	{
		$first = $this->proxy->getAssetManager();
		$this->assertSame($first, $this->proxy->pubGetAssetManagerDirect());
		$this->assertSame($first, $this->proxy->getAssetManager());
	}

	public function testGetProxyBackingReturnsBackingModule(): void
	{
		$this->assertSame($this->backend, $this->proxy->getProxyBacking());
	}

	public function testGetAssetManagerThrowsWhenIdEmpty(): void
	{
		$proxy = new TAssetManagerProxy();
		try {
			$proxy->getAssetManager();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('assetmanagerproxy_backing_asset_manager_id_required', $e->getErrorCode());
		}
	}

	public function testGetAssetManagerThrowsWhenModuleNotFound(): void
	{
		$proxy = new TAssetManagerProxy();
		$proxy->setBackingAssetManagerId('missingModule');
		try {
			$proxy->getAssetManager();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('assetmanagerproxy_asset_manager_not_found', $e->getErrorCode());
		}
	}

	public function testGetAssetManagerThrowsWhenModuleIsNotTAssetManager(): void
	{
		$notAnAssetManager = new TAssetManagerProxyNotAnAssetManagerModule();
		$this->app->setModule('notAssets', $notAnAssetManager);

		$proxy = new TAssetManagerProxy();
		$proxy->setBackingAssetManagerId('notAssets');
		try {
			$proxy->getAssetManager();
			$this->fail('Expected TConfigurationException was not thrown.');
		} catch (TConfigurationException $e) {
			$this->assertSame('assetmanagerproxy_invalid_asset_manager_type', $e->getErrorCode());
		} finally {
			$proxy->unlisten();
			$notAnAssetManager->unlisten();
		}
	}

	// ── Property delegation ─────────────────────────────────────────────────────

	public function testBasePathAndBaseUrlReadFromBacking(): void
	{
		$this->assertSame($this->assetDir, $this->proxy->getBasePath());
		$this->assertSame('/assets', $this->proxy->getBaseUrl());
	}

	public function testBasePathSetterDelegatesAndHonorsBackingInitialization(): void
	{
		// The backend is initialized, so its own guard rejects the change.
		$this->expectException(TInvalidOperationException::class);
		$this->proxy->setBasePath($this->aliasFor($this->sourceDir));
	}

	public function testBaseUrlSetterDelegatesToUninitializedBacking(): void
	{
		$fresh = new TAssetManagerProxyBackend();
		$this->app->setModule('freshAssets', $fresh);
		$proxy = new TAssetManagerProxy();
		$proxy->setBackingAssetManagerId('freshAssets');

		$proxy->setBaseUrl('/other/');
		$proxy->setBasePath($this->aliasFor($this->sourceDir));

		$this->assertSame('/other', $fresh->getBaseUrl());
		$this->assertSame($this->sourceDir, $fresh->getBasePath());
		$proxy->unlisten();
		$fresh->unlisten();
	}

	public function testBooleanPropertiesDelegate(): void
	{
		$this->proxy->setLinkAssets(true);
		$this->assertTrue($this->backend->getLinkAssets());
		$this->assertTrue($this->proxy->getLinkAssets());

		$this->proxy->setForceCopy('true');
		$this->assertTrue($this->backend->getForceCopy());
		$this->assertTrue($this->proxy->getForceCopy());

		$this->assertTrue($this->proxy->getAtomic());
		$this->proxy->setAtomic(false);
		$this->assertFalse($this->backend->getAtomic());
		$this->assertFalse($this->proxy->getAtomic());

		$this->proxy->setAppendTimestamp(true);
		$this->assertTrue($this->backend->getAppendTimestamp());
		$this->assertTrue($this->proxy->getAppendTimestamp());

		$this->proxy->setCaseSensitive(false);
		$this->assertFalse($this->backend->getCaseSensitive());
		$this->assertFalse($this->proxy->getCaseSensitive());
	}

	public function testTimestampVarDelegates(): void
	{
		$this->assertSame('v', $this->proxy->getTimestampVar());
		$this->proxy->setTimestampVar('ts');
		$this->assertSame('ts', $this->backend->getTimestampVar());
		$this->assertSame('ts', $this->proxy->getTimestampVar());
	}

	public function testCallbackPropertiesDelegate(): void
	{
		$hash = fn ($path) => 'h';
		$before = fn ($src, $dst) => true;
		$after = fn ($src, $dst) => null;

		$this->proxy->setHashCallback($hash);
		$this->proxy->setBeforeCopy($before);
		$this->proxy->setAfterCopy($after);

		$this->assertSame($hash, $this->backend->getHashCallback());
		$this->assertSame($hash, $this->proxy->getHashCallback());
		$this->assertSame($before, $this->backend->getBeforeCopy());
		$this->assertSame($before, $this->proxy->getBeforeCopy());
		$this->assertSame($after, $this->backend->getAfterCopy());
		$this->assertSame($after, $this->proxy->getAfterCopy());

		$this->proxy->setHashCallback(null);
		$this->assertNull($this->proxy->getHashCallback());
	}

	public function testArrayPropertiesDelegate(): void
	{
		$this->proxy->setAssetMap(['a.js' => '/x/a.js']);
		$this->assertSame(['a.js' => '/x/a.js'], $this->backend->getAssetMap());
		$this->assertSame(['a.js' => '/x/a.js'], $this->proxy->getAssetMap());

		$this->assertNull($this->proxy->getOnly());
		$this->proxy->setOnly(['*.js']);
		$this->assertSame(['*.js'], $this->backend->getOnly());
		$this->assertSame(['*.js'], $this->proxy->getOnly());

		$this->assertNull($this->proxy->getExcept());
		$this->proxy->setExcept(['*.map']);
		$this->assertSame(['*.map'], $this->backend->getExcept());
		$this->assertSame(['*.map'], $this->proxy->getExcept());
	}

	public function testModePropertiesDelegate(): void
	{
		$this->assertNull($this->proxy->getFileMode());
		$this->proxy->setFileMode(0o644);
		$this->assertSame(0o644, $this->backend->getFileMode());
		$this->assertSame(0o644, $this->proxy->getFileMode());

		$this->assertSame(Prado::getDefaultDirPermissions(), $this->proxy->getDirMode());
		$this->proxy->setDirMode(0o750);
		$this->assertSame(0o750, $this->backend->getDirMode());
		$this->assertSame(0o750, $this->proxy->getDirMode());
	}

	public function testPropertyAccessThroughMagicUsesProxyOverrides(): void
	{
		$this->proxy->AppendTimestamp = true;
		$this->assertTrue($this->backend->getAppendTimestamp());
		$this->assertTrue($this->proxy->AppendTimestamp);
		$this->assertSame('backingAssets', $this->proxy->BackingAssetManagerId);
	}

	// ── Publishing delegation ───────────────────────────────────────────────────

	public function testPublishFilePathReturnsBackingUrl(): void
	{
		$this->proxy->init(null);
		$file = $this->writeSourceFile('hello.txt', 'hello');

		$url = $this->proxy->publishFilePath($file);

		$this->assertSame($this->backend->getPublishedUrl($file), $url);
		$this->assertStringStartsWith('/assets/', $url);
		$this->assertStringEndsWith('/hello.txt', $url);
		$this->assertFileExists($this->backend->getPublishedPath($file));
		$this->assertSame('hello', file_get_contents($this->backend->getPublishedPath($file)));
	}

	public function testPublishDelegatesAndSharesPublishedCache(): void
	{
		$file = $this->writeSourceFile('shared.txt', 'x');
		$url = $this->proxy->publish($file);

		$this->assertSame($url, $this->backend->publish($file));
		$this->assertSame($this->backend->getPublished(), $this->proxy->getPublished());
		$this->assertArrayHasKey($file, $this->proxy->getPublished());
	}

	public function testGetPublishedPathAndUrlDelegate(): void
	{
		$file = $this->writeSourceFile('loc.txt', 'x');
		$this->assertSame($this->backend->getPublishedPath($file), $this->proxy->getPublishedPath($file));
		$this->assertSame($this->backend->getPublishedUrl($file), $this->proxy->getPublishedUrl($file));
	}

	public function testCopyDirectoryDelegates(): void
	{
		$this->writeSourceFile('a.js', 'a');
		$this->writeSourceFile('b.css', 'b');
		$dst = dirname($this->assetDir) . DIRECTORY_SEPARATOR . 'copy';

		$this->proxy->copyDirectory($this->sourceDir, $dst, ['only' => ['*.js']]);

		$this->assertFileExists($dst . DIRECTORY_SEPARATOR . 'a.js');
		$this->assertFileDoesNotExist($dst . DIRECTORY_SEPARATOR . 'b.css');
	}

	public function testResolveAssetDelegates(): void
	{
		$this->backend->setAssetMap(['lib/app.js' => '/cdn/app.js']);
		$this->assertSame('/cdn/app.js', $this->proxy->resolveAsset('lib/app.js'));
		$this->assertSame('/cdn/app.js', $this->proxy->resolveAsset('app.js', 'lib'));
		$this->assertNull($this->proxy->resolveAsset('none.js'));
	}

	public function testValidateSymlinksDelegates(): void
	{
		$this->assertSame(0, $this->proxy->validateSymlinks());
		$this->assertNull($this->proxy->validateSymlinks($this->sourceDir . '/missing'));
	}

	public function testPublishTarFileDelegatesChecksumValidation(): void
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$this->proxy->publishTarFile($this->sourceDir . '/x.tar', $this->sourceDir . '/x.md5');
	}

	// ── __call / __get / __set passthrough ──────────────────────────────────────

	public function testCallForwardsPublicMethodToBacking(): void
	{
		$this->assertSame('custom:hello', $this->proxy->customMethod('hello'));
	}

	public function testCallUnknownMethodThrows(): void
	{
		$this->expectException(TUnknownMethodException::class);
		$this->proxy->totallyUnknownMethod();
	}

	public function testMagicPropertyForwardsToBacking(): void
	{
		$this->assertFalse(isset($this->proxy->CustomProp));
		$this->proxy->CustomProp = 'world';
		$this->assertSame('world', $this->backend->getCustomProp());
		$this->assertSame('world', $this->proxy->CustomProp);
		$this->assertTrue(isset($this->proxy->CustomProp));
		unset($this->proxy->CustomProp);
		$this->assertNull($this->backend->getCustomProp());
	}

	public function testSetProxyReadOnlyPropertyThrows(): void
	{
		$this->expectException(TInvalidOperationException::class);
		$this->proxy->AssetManager = 'anything';
	}

	// ── isa() transparency ──────────────────────────────────────────────────────

	public function testIsaReturnsTrueForOwnHierarchy(): void
	{
		$this->assertTrue($this->proxy->isa(TAssetManagerProxy::class));
		$this->assertTrue($this->proxy->isa(TAssetManager::class));
		$this->assertTrue($this->proxy->isa(IProxy::class));
	}

	public function testIsaLazilyResolvesAndSeesBackingClass(): void
	{
		$this->assertNull($this->proxy->pubGetAssetManagerDirect());
		$this->assertTrue($this->proxy->isa(TAssetManagerProxyBackend::class));
		$this->assertNotNull($this->proxy->pubGetAssetManagerDirect());
	}

	public function testIsaReturnsFalseForUnrelatedClassAndWithoutId(): void
	{
		$this->assertFalse($this->proxy->isa(TAssetManagerProxyNotAnAssetManagerModule::class));
		$fresh = new TAssetManagerProxyAccessor();
		$this->assertFalse($fresh->isa(TAssetManagerProxyBackend::class));
	}

	// ── __clone ─────────────────────────────────────────────────────────────────

	public function testCloneClearsBackingAndPreservesId(): void
	{
		$this->proxy->getAssetManager();
		$clone = clone $this->proxy;

		$this->assertNull($clone->pubGetAssetManagerDirect());
		$this->assertSame('backingAssets', $clone->getBackingAssetManagerId());
		$this->assertSame($this->backend, $clone->getAssetManager());
	}

	public function testCloneDetachesProxyEvents(): void
	{
		$this->proxy->getAssetManager();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));

		$clone = clone $this->proxy;

		$this->assertFalse($clone->hasEvent('OnTestEvent'));
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));
	}

	// ── Serialization ───────────────────────────────────────────────────────────

	public function testZappableAlwaysExcludesBackingAndForwarders(): void
	{
		$exprops = [];
		$this->proxy->pubGetZappableSleepProps($exprops);

		$this->assertContains("\0" . TAssetManagerProxy::class . "\0_proxyBacking", $exprops);
		$this->assertContains("\0" . TAssetManagerProxy::class . "\0_proxyEventNames", $exprops);
		$this->assertNotContains("\0" . TAssetManagerProxy::class . "\0_backingAssetManagerId", $exprops);
	}

	public function testZappableExcludesBackingIdWhenEmpty(): void
	{
		$proxy = new TAssetManagerProxyAccessor();
		$exprops = [];
		$proxy->pubGetZappableSleepProps($exprops);

		$this->assertContains("\0" . TAssetManagerProxy::class . "\0_backingAssetManagerId", $exprops);
	}

	public function testUnserializedProxyReresolvesBacking(): void
	{
		$this->proxy->getAssetManager();
		$copy = unserialize(serialize($this->proxy));

		$this->assertInstanceOf(TAssetManagerProxyAccessor::class, $copy);
		$this->assertNull($copy->pubGetAssetManagerDirect());
		$this->assertSame('backingAssets', $copy->getBackingAssetManagerId());
		$this->assertSame($this->backend, $copy->getAssetManager());
		$copy->unlisten();
	}

	// ── Event forwarding ────────────────────────────────────────────────────────

	public function testBackingEventNotExposedBeforeResolution(): void
	{
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	public function testResolutionAttachesBackingEvents(): void
	{
		$this->proxy->getAssetManager();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));
		$this->assertInstanceOf(TWeakCallableCollection::class, $this->proxy->OnTestEvent);
	}

	public function testHandlerOnProxyFiresWhenBackingRaises(): void
	{
		$this->proxy->getAssetManager();
		$fired = false;
		$this->proxy->OnTestEvent = function () use (&$fired) {
			$fired = true;
		};
		$this->backend->onTestEvent(new TEventParameter());
		$this->assertTrue($fired);
	}

	public function testBackingIdChangeDetachesEvents(): void
	{
		$this->proxy->getAssetManager();
		$this->assertTrue($this->proxy->hasEvent('OnTestEvent'));
		$this->proxy->setBackingAssetManagerId('someOtherAssets');
		$this->assertFalse($this->proxy->hasEvent('OnTestEvent'));
	}

	// ── Helpers ─────────────────────────────────────────────────────────────────

	private function newBackend(string $basePath, string $baseUrl): TAssetManagerProxyBackend
	{
		$backend = new TAssetManagerProxyBackend();
		$backend->setBasePath($this->aliasFor($basePath));
		$backend->setBaseUrl($baseUrl);
		$backend->init(null);
		return $backend;
	}

	/**
	 * Registers a path alias for a directory, because BasePath takes namespace form.
	 * @param string $dir the directory to alias
	 * @return string the alias name to pass to setBasePath()
	 */
	private function aliasFor(string $dir): string
	{
		$alias = 'AmProxy' . sprintf('%x', crc32($dir));
		Prado::setPathOfAlias($alias, $dir);
		return $alias;
	}

	private function writeSourceFile(string $name, string $content): string
	{
		$file = $this->sourceDir . DIRECTORY_SEPARATOR . $name;
		file_put_contents($file, $content);
		return $file;
	}

	private function countLogs(int $level, string $category): int
	{
		return count(Prado::getLogger()->getLogs($level, $category));
	}

	private function removeDirectory(string $dir): void
	{
		if (!is_dir($dir)) {
			return;
		}
		foreach (scandir($dir) as $entry) {
			if ($entry === '.' || $entry === '..') {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $entry;
			if (is_dir($path) && !is_link($path)) {
				$this->removeDirectory($path);
			} else {
				unlink($path);
			}
		}
		rmdir($dir);
	}
}

/**
 * A TBehavior recording dyInit calls, used to verify init() reaches TModule::init().
 */
class TAssetManagerProxyInitBehavior extends \Prado\Util\TBehavior
{
	public int $initCalls = 0;
	public mixed $lastConfig = null;

	public function dyInit($config, ?\Prado\Util\TCallChain $chain = null)
	{
		$this->initCalls++;
		$this->lastConfig = $config;
		return $chain?->dyInit($config);
	}
}
