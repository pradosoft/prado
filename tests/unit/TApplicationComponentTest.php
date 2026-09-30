<?php

/**
 * TApplicationComponentTest unit tests.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TApplicationComponent;
use Prado\TApplicationMode;

// ============================================================================
// Fixtures
// ============================================================================

/**
 * Concrete subclass that exposes every protected method under test as a
 * public `pub*` wrapper so tests avoid per-call ReflectionMethod overhead.
 */
class TApplicationComponentTest_Concrete extends TApplicationComponent
{
	public function pubGetApplicationDirect(): ?TApplication
	{
		return $this->getApplicationDirect();
	}

	public function pubSetApplicationDirect(?TApplication $app): void
	{
		$this->setApplicationDirect($app);
	}

	public function pubResolveApplication(): void
	{
		$this->resolveApplication();
	}

	public function pubGetClassFxEvents(object $class): array
	{
		return $this->getClassFxEvents($class);
	}
}

/**
 * Overrides `getApplication()` to return `null`, isolating the null-app guard
 * branches in every shortcut property without requiring global-app surgery.
 */
class TApplicationComponentTest_NullApp extends TApplicationComponent
{
	public function getApplication(): TApplication
	{
		throw new TInvalidOperationException('applicationcomponent_application_required', static::class);
	}
}

/**
 * Intercepts `publishFilePath()` so asset-path construction can be verified
 * without touching the real asset manager.
 */
class TApplicationComponentTest_AssetTracker extends TApplicationComponent
{
	public ?string $lastFullPath = null;
	public mixed $lastCheckTimestamp = null;
	public string $returnUrl = 'asset://tracked';

	public function publishFilePath($fullPath, $checkTimestamp = false)
	{
		$this->lastFullPath = $fullPath;
		$this->lastCheckTimestamp = $checkTimestamp;
		return $this->returnUrl;
	}
}

/**
 * Dedicated fixture whose fx-event cache entry is guaranteed absent at the
 * start of the Performance-mode write test (a fresh class name is all that is
 * required because TApplicationComponent::getClassFxEvents() uses a static
 * local keyed by class name).  Extends TApplicationComponentTest_Concrete so
 * that pubGetClassFxEvents() is available without re-declaration.
 */
class TApplicationComponentTest_FxCacheProbe extends TApplicationComponentTest_Concrete {}

// ============================================================================
// Test suite
// ============================================================================

/**
 * Tests for {@see \Prado\TApplicationComponent}.
 */
class TApplicationComponentTest extends TestCase
{
	private TApplicationComponentTest_Concrete $comp;

	protected function setUp(): void
	{
		$this->comp = new TApplicationComponentTest_Concrete();
	}

	// ════════════════════════════════════════════════════════════════════════
	// Constants
	// ════════════════════════════════════════════════════════════════════════

	public function testFxCacheFileConstant(): void
	{
		self::assertSame('fxevent.cache', TApplicationComponent::FX_CACHE_FILE);
	}

	// ════════════════════════════════════════════════════════════════════════
	// Construction and resolveApplication
	// ════════════════════════════════════════════════════════════════════════

	public function testConstructorPicksUpGlobalApplication(): void
	{
		$app = Prado::getApplication();
		self::assertNotNull($app);
		$comp = new TApplicationComponentTest_Concrete();
		self::assertSame($app, $comp->pubGetApplicationDirect());
	}

	public function testConstructorWithNoGlobalApplicationLeavesNull(): void
	{
		$snap = PradoUnit::snapshotStatic(Prado::class, ['_application']);
		PradoUnit::setStaticProp(Prado::class, '_application', null);
		try {
			$comp = new TApplicationComponentTest_Concrete();
			self::assertNull($comp->pubGetApplicationDirect());
		} finally {
			PradoUnit::restoreStatic(Prado::class, $snap);
		}
	}

	public function testResolveApplicationFillsNullFromGlobal(): void
	{
		$global = Prado::getApplication();
		$this->comp->pubSetApplicationDirect(null);
		$this->comp->pubResolveApplication();
		self::assertSame($global, $this->comp->pubGetApplicationDirect());
	}

	public function testResolveApplicationDoesNotOverwriteExistingApp(): void
	{
		// Component was constructed with the bootstrap app already stored.
		$bootstrap = Prado::getApplication();
		self::assertSame($bootstrap, $this->comp->pubGetApplicationDirect());

		// Replace the global singleton with a different application instance.
		$other = new TTestApplication();
		try {
			self::assertNotSame($bootstrap, $other);
			self::assertSame($other, Prado::getApplication());

			// resolveApplication() must not overwrite the component's existing $_application
			// even though the global singleton has changed.
			$this->comp->pubResolveApplication();
			self::assertSame($bootstrap, $this->comp->pubGetApplicationDirect());
		} finally {
			$other->restoreApplication();
		}
	}

	public function testResolveApplicationNoOpWhenAlreadySet(): void
	{
		// Component was constructed with the global app already set.
		$original = $this->comp->pubGetApplicationDirect();
		self::assertNotNull($original);
		$this->comp->pubResolveApplication();
		self::assertSame($original, $this->comp->pubGetApplicationDirect());
	}

	// ════════════════════════════════════════════════════════════════════════
	// getAutoGlobalListen
	// ════════════════════════════════════════════════════════════════════════

	public function testGetAutoGlobalListenAlwaysTrue(): void
	{
		self::assertTrue($this->comp->getAutoGlobalListen());
	}

	// ════════════════════════════════════════════════════════════════════════
	// getApplication / getApplicationDirect / setApplicationDirect
	// ════════════════════════════════════════════════════════════════════════

	public function testGetApplicationReturnsSameAsGlobal(): void
	{
		self::assertSame(Prado::getApplication(), $this->comp->getApplication());
	}

	public function testGetApplicationCallsResolveWhenDirectIsNull(): void
	{
		$global = Prado::getApplication();
		$this->comp->pubSetApplicationDirect(null);
		// getApplication() must re-resolve from the global singleton.
		self::assertSame($global, $this->comp->getApplication());
		// After the call, the direct store must also be repopulated.
		self::assertSame($global, $this->comp->pubGetApplicationDirect());
	}

	public function testGetApplicationDirectReturnsRawStoreWithoutResolving(): void
	{
		$app = Prado::getApplication();
		self::assertSame($app, $this->comp->pubGetApplicationDirect());

		$this->comp->pubSetApplicationDirect(null);
		// Direct must return null — no lazy-resolve triggered.
		self::assertNull($this->comp->pubGetApplicationDirect());
	}

	public function testSetApplicationDirectStoresValue(): void
	{
		$other = new TTestApplication();
		try {
			$this->comp->pubSetApplicationDirect($other);
			self::assertSame($other, $this->comp->pubGetApplicationDirect());
		} finally {
			$other->restoreApplication();
		}
	}

	public function testCollectedApplicationStaysBoundAndYieldsNull(): void
	{
		$snap = PradoUnit::snapshotStatic(Prado::class, ['_application', '_applications']);
		try {
			$other = new TTestApplication();
			$this->comp->pubSetApplicationDirect($other);
			self::assertSame($other, $this->comp->pubGetApplicationDirect());

			Prado::unregisterApplication($other);
			unset($other);
			gc_collect_cycles();

			// The binding is weak: the application is gone and the component does not rebind.
			self::assertNull($this->comp->pubGetApplicationDirect());
			self::assertFalse($this->comp->hasApplication());
			$this->expectException(TInvalidOperationException::class);
			$this->comp->getApplication();
		} finally {
			PradoUnit::restoreStatic(Prado::class, $snap);
		}
	}

	public function testDestructedApplicationStaysBoundAndYieldsNull(): void
	{
		$other = new TTestApplication();
		try {
			$this->comp->pubSetApplicationDirect($other);
			self::assertSame($other, $this->comp->findApplication());

			// A destructed application reads as collected even while PHP still holds the object.
			$other->__destruct();
			self::assertNull($this->comp->pubGetApplicationDirect());
			self::assertNull($this->comp->findApplication());
			self::assertFalse($this->comp->hasApplication());
			$this->expectException(TInvalidOperationException::class);
			$this->comp->getApplication();
		} finally {
			$other->restoreApplication();
		}
	}

	public function testFindApplicationAndHasApplicationWithoutAnApplication(): void
	{
		$snap = PradoUnit::snapshotStatic(Prado::class, ['_application']);
		PradoUnit::setStaticProp(Prado::class, '_application', null);
		try {
			$comp = new TApplicationComponentTest_Concrete();
			self::assertNull($comp->findApplication());
			self::assertFalse($comp->hasApplication());
			$this->expectException(TInvalidOperationException::class);
			$comp->getApplication();
		} finally {
			PradoUnit::restoreStatic(Prado::class, $snap);
		}
	}

	public function testFindApplicationReturnsTheBoundApplication(): void
	{
		self::assertSame(Prado::getApplication(), $this->comp->findApplication());
		self::assertTrue($this->comp->hasApplication());
	}

	public function testSetApplicationDirectNull(): void
	{
		$this->comp->pubSetApplicationDirect(null);
		self::assertNull($this->comp->pubGetApplicationDirect());
	}

	public function testSetApplicationDirectOverwritesPreviousValue(): void
	{
		$first = new TTestApplication();
		try {
			$this->comp->pubSetApplicationDirect($first);
			self::assertSame($first, $this->comp->pubGetApplicationDirect());
		} finally {
			$first->restoreApplication();
		}

		$this->comp->pubSetApplicationDirect(null);
		self::assertNull($this->comp->pubGetApplicationDirect());
	}

	// ════════════════════════════════════════════════════════════════════════
	// Shortcut properties — with application
	// ════════════════════════════════════════════════════════════════════════

	public function testGetServiceDelegatesToApplication(): void
	{
		$app = $this->comp->getApplication();
		self::assertSame($app->getService(), $this->comp->getService());
	}

	public function testGetRequestDelegatesToApplication(): void
	{
		$app = $this->comp->getApplication();
		self::assertSame($app->getRequest(), $this->comp->getRequest());
	}

	public function testGetResponseDelegatesToApplication(): void
	{
		$app = $this->comp->getApplication();
		$levelBefore = ob_get_level();
		$appResp  = $app->getResponse();
		$compResp = $this->comp->getResponse();
		// THttpResponse::init() calls ob_start(); close any extra buffers opened.
		while (ob_get_level() > $levelBefore) {
			ob_end_clean();
		}
		self::assertSame($appResp, $compResp);
	}

	public function testGetSessionDelegatesToApplication(): void
	{
		$app = $this->comp->getApplication();
		self::assertSame($app->getSession(), $this->comp->getSession());
	}

	public function testGetUserDelegatesToApplication(): void
	{
		$app = $this->comp->getApplication();
		self::assertSame($app->getUser(), $this->comp->getUser());
	}

	// ════════════════════════════════════════════════════════════════════════
	// Shortcut properties — null-app guard (nullsafe operator paths)
	// ════════════════════════════════════════════════════════════════════════

	public function testGetServiceThrowsWithNoApp(): void
	{
		$this->expectException(TInvalidOperationException::class);
		(new TApplicationComponentTest_NullApp())->getService();
	}

	public function testGetRequestThrowsWithNoApp(): void
	{
		$this->expectException(TInvalidOperationException::class);
		(new TApplicationComponentTest_NullApp())->getRequest();
	}

	public function testGetResponseThrowsWithNoApp(): void
	{
		$this->expectException(TInvalidOperationException::class);
		(new TApplicationComponentTest_NullApp())->getResponse();
	}

	public function testGetSessionThrowsWithNoApp(): void
	{
		$this->expectException(TInvalidOperationException::class);
		(new TApplicationComponentTest_NullApp())->getSession();
	}

	public function testGetUserThrowsWithNoApp(): void
	{
		$this->expectException(TInvalidOperationException::class);
		(new TApplicationComponentTest_NullApp())->getUser();
	}

	// ════════════════════════════════════════════════════════════════════════
	// publishFilePath
	// ════════════════════════════════════════════════════════════════════════

	public function testPublishFilePathThrowsWithNoApp(): void
	{
		// TApplicationComponentTest_NullApp::getApplication() returns null,
		// so the nullsafe chain short-circuits to null.
		$this->expectException(TInvalidOperationException::class);
		(new TApplicationComponentTest_NullApp())->publishFilePath('/any/path');
	}

	// ════════════════════════════════════════════════════════════════════════
	// publishAsset
	// ════════════════════════════════════════════════════════════════════════

	public function testPublishAssetDefaultsToSelfClass(): void
	{
		$tracker = new TApplicationComponentTest_AssetTracker();
		$tracker->publishAsset('images/logo.png');

		// The full path must be relative to the file that declares
		// TApplicationComponentTest_AssetTracker — this test file.
		$expectedDir = dirname((new \ReflectionClass(TApplicationComponentTest_AssetTracker::class))->getFileName());
		$expected = $expectedDir . DIRECTORY_SEPARATOR . 'images/logo.png';
		self::assertSame($expected, $tracker->lastFullPath);
	}

	public function testPublishAssetExplicitClassName(): void
	{
		$tracker = new TApplicationComponentTest_AssetTracker();
		$tracker->publishAsset('assets/style.css', TApplicationComponent::class);

		// Path must resolve relative to TApplicationComponent's own source file.
		$expectedDir = dirname((new \ReflectionClass(TApplicationComponent::class))->getFileName());
		$expected = $expectedDir . DIRECTORY_SEPARATOR . 'assets/style.css';
		self::assertSame($expected, $tracker->lastFullPath);
	}

	public function testPublishAssetReturnsUrlFromPublishFilePath(): void
	{
		$tracker = new TApplicationComponentTest_AssetTracker();
		$tracker->returnUrl = 'https://example.com/asset/logo.png';
		$result = $tracker->publishAsset('logo.png');
		self::assertSame('https://example.com/asset/logo.png', $result);
	}

	public function testPublishAssetThrowsWithNoApp(): void
	{
		// publishAsset() calls $this->publishFilePath(), which in turn calls
		// $this->getApplication()?->getAssetManager()?->publishFilePath(...).
		// When getApplication() returns null the nullsafe chain short-circuits to null.
		$this->expectException(TInvalidOperationException::class);
		(new TApplicationComponentTest_NullApp())->publishAsset('foo.png');
	}

	// ════════════════════════════════════════════════════════════════════════
	// isCurrentApplication
	// ════════════════════════════════════════════════════════════════════════

	public function testIsCurrentApplicationReturnsTrueWhenMatchesGlobal(): void
	{
		// Component was constructed with the bootstrap app; global is the same instance.
		self::assertTrue($this->comp->isCurrentApplication());
	}

	public function testIsCurrentApplicationReturnsFalseWhenDirectIsNull(): void
	{
		$this->comp->pubSetApplicationDirect(null);
		self::assertFalse($this->comp->isCurrentApplication());
	}

	public function testIsCurrentApplicationReturnsFalseWhenDifferentFromGlobal(): void
	{
		// Component holds the bootstrap app; replace the global with a different instance.
		$other = new TTestApplication();
		try {
			// Global is now $other, component still holds the bootstrap app.
			self::assertNotSame($this->comp->pubGetApplicationDirect(), Prado::getApplication());
			self::assertFalse($this->comp->isCurrentApplication());
		} finally {
			$other->restoreApplication();
		}
	}

	public function testIsCurrentApplicationReturnsFalseWhenBothNull(): void
	{
		// Even when both the component store and the global are null, the method
		// must return false because null is not a valid "current application."
		$snap = PradoUnit::snapshotStatic(Prado::class, ['_application']);
		PradoUnit::setStaticProp(Prado::class, '_application', null);
		try {
			$this->comp->pubSetApplicationDirect(null);
			self::assertFalse($this->comp->isCurrentApplication());
		} finally {
			PradoUnit::restoreStatic(Prado::class, $snap);
		}
	}

	// ════════════════════════════════════════════════════════════════════════
	// makeCurrentApplication
	// ════════════════════════════════════════════════════════════════════════

	public function testSetAsCurrentApplicationPromotesAppToGlobal(): void
	{
		// Component holds the bootstrap app; replace the global with $other.
		$bootstrap = $this->comp->pubGetApplicationDirect();
		$other = new TTestApplication();
		try {
			self::assertSame($other, Prado::getApplication());
			self::assertNotSame($bootstrap, Prado::getApplication());

			// Promote the component's app back to the global.
			$this->comp->makeCurrentApplication();
			self::assertSame($bootstrap, Prado::getApplication());
		} finally {
			// restoreApplication() restores the original global (the bootstrap app),
			// which is already current after makeCurrentApplication(); safe to call.
			$other->restoreApplication();
		}
	}

	public function testSetAsCurrentApplicationNoOpWhenNull(): void
	{
		$global = Prado::getApplication();
		$this->comp->pubSetApplicationDirect(null);
		$this->comp->makeCurrentApplication();
		// Global must be unchanged.
		self::assertSame($global, Prado::getApplication());
	}

	public function testSetAsCurrentApplicationNoOpWhenAlreadyCurrent(): void
	{
		// Component's app already is the global; makeCurrentApplication() is idempotent.
		$global = Prado::getApplication();
		self::assertTrue($this->comp->isCurrentApplication());
		$this->comp->makeCurrentApplication();
		self::assertSame($global, Prado::getApplication());
		self::assertTrue($this->comp->isCurrentApplication());
	}

	// ════════════════════════════════════════════════════════════════════════
	// getClassFxEvents — return shape
	// ════════════════════════════════════════════════════════════════════════

	public function testGetClassFxEventsReturnsArray(): void
	{
		$fx = $this->comp->pubGetClassFxEvents($this->comp);
		self::assertIsArray($fx);
	}

	public function testGetClassFxEventsContainsOnlyStrings(): void
	{
		$fx = $this->comp->pubGetClassFxEvents($this->comp);
		foreach ($fx as $event) {
			self::assertIsString($event);
		}
	}

	public function testGetClassFxEventsAllStartWithFx(): void
	{
		$fx = $this->comp->pubGetClassFxEvents($this->comp);
		foreach ($fx as $event) {
			self::assertStringStartsWith('fx', $event, "Event '{$event}' does not start with 'fx'.");
		}
	}

	public function testGetClassFxEventsContainsKnownTComponentEvents(): void
	{
		$fx = $this->comp->pubGetClassFxEvents($this->comp);
		self::assertContains('fxAttachClassBehavior', $fx);
		self::assertContains('fxDetachClassBehavior', $fx);
	}

	public function testGetClassFxEventsResultIsStable(): void
	{
		// Second call must return the same array contents (served from cache).
		$first = $this->comp->pubGetClassFxEvents($this->comp);
		$second = $this->comp->pubGetClassFxEvents($this->comp);
		self::assertSame($first, $second);
	}

	public function testGetClassFxEventsDifferentClassesReturnDifferentEntries(): void
	{
		$comp2 = new TApplicationComponentTest_FxCacheProbe();
		// Both classes share the same fx events since neither adds new ones;
		// verify the method at least runs for a second class without error.
		$fx1 = $this->comp->pubGetClassFxEvents($this->comp);
		$fx2 = $this->comp->pubGetClassFxEvents($comp2);
		self::assertIsArray($fx2);
		// Both extend TApplicationComponent so their fx event sets must be equal.
		sort($fx1);
		sort($fx2);
		self::assertSame($fx1, $fx2);
	}

	// ════════════════════════════════════════════════════════════════════════
	// getClassFxEvents — cache file (Performance mode)
	// ════════════════════════════════════════════════════════════════════════

	/**
	 * @runInSeparateProcess
	 */
	public function testGetClassFxEventsWritesCacheFileInPerformanceMode(): void
	{
		$otherApp = new TTestApplication();
		try {
			$otherApp->setMode(TApplicationMode::Performance);

			// In a fresh process the static-local $_classfx is empty, so the
			// first lookup always reaches the write branch in Performance mode.
			$probe = new TApplicationComponentTest_FxCacheProbe();
			$fx = $probe->pubGetClassFxEvents($probe);

			$cacheFile = $otherApp->getRuntimePath() . DIRECTORY_SEPARATOR . TApplicationComponent::FX_CACHE_FILE;
			self::assertFileExists($cacheFile);

			// The serialized map must contain the probe class's fx event list.
			$content = file_get_contents($cacheFile);
			$cached = @unserialize($content);
			self::assertIsArray($cached);
			self::assertArrayHasKey(TApplicationComponentTest_FxCacheProbe::class, $cached);
			self::assertSame($fx, $cached[TApplicationComponentTest_FxCacheProbe::class]);
		} finally {
			$otherApp->restoreApplication();
		}
	}

	public function testGetClassFxEventsDebugModeDoesNotWriteCacheFile(): void
	{
		// Default app mode is Debug — the cache file must NOT be written.
		$app = Prado::getApplication();
		self::assertSame(TApplicationMode::Debug, $app->getMode());

		$cacheFile = $app->getRuntimePath() . DIRECTORY_SEPARATOR . TApplicationComponent::FX_CACHE_FILE;

		// Remove any pre-existing file so the assertion is unambiguous.
		if (file_exists($cacheFile)) {
			unlink($cacheFile);
		}

		$this->comp->pubGetClassFxEvents($this->comp);

		self::assertFileDoesNotExist($cacheFile);
	}

	/**
	 * @runInSeparateProcess
	 */
	public function testGetClassFxEventsNormalModeWritesCacheWhenNewPradoClassAdded(): void
	{
		$otherApp = new TTestApplication();
		try {
			$otherApp->setMode(TApplicationMode::Normal);

			$cacheFile = $otherApp->getRuntimePath() . DIRECTORY_SEPARATOR . TApplicationComponent::FX_CACHE_FILE;
			if (file_exists($cacheFile)) {
				unlink($cacheFile);
			}

			// TApplicationComponent is a PRADO framework class (present in Prado::$classMap
			// as 'TApplicationComponent' => 'Prado\TApplicationComponent').  In a fresh
			// process $_classfxSize starts at 0, so the first lookup of any PRADO class
			// drives count($classData) above 0 and triggers a write.
			$probe = new TApplicationComponentTest_Concrete();
			$tac = new TApplicationComponent();
			$probe->pubGetClassFxEvents($tac);

			self::assertFileExists($cacheFile);

			$content = file_get_contents($cacheFile);
			$cached = @unserialize($content);
			self::assertIsArray($cached);
			self::assertArrayHasKey(TApplicationComponent::class, $cached);
		} finally {
			$otherApp->restoreApplication();
		}
	}

	// ════════════════════════════════════════════════════════════════════════
	// Inheritance sanity
	// ════════════════════════════════════════════════════════════════════════

	public function testExtendsComponent(): void
	{
		self::assertInstanceOf(\Prado\TComponent::class, $this->comp);
	}

	public function testSubclassInheritsApplication(): void
	{
		// Modules, services, and controls all extend TApplicationComponent.
		// Verify that a subclass resolves the application correctly.
		$sub = new TApplicationComponentTest_FxCacheProbe();
		self::assertSame(Prado::getApplication(), $sub->getApplication());
	}

	// ════════════════════════════════════════════════════════════════════════
	// Serialization — _getZappableSleepProps / __wakeup
	// ════════════════════════════════════════════════════════════════════════

	public function testSerializeDoesNotIncludeApplication(): void
	{
		// $_application must be excluded so serialized components do not drag
		// in the entire TApplication graph.
		$serialized = serialize($this->comp);
		self::assertStringNotContainsString('Prado\\TApplication', $serialized,
			'Serialized TApplicationComponent must not embed a TApplication instance.');
	}

	public function testWakeupRestoresApplicationFromGlobalSingleton(): void
	{
		$app = Prado::getApplication();
		$serialized = serialize($this->comp);

		/** @var TApplicationComponentTest_Concrete $restored */
		$restored = unserialize($serialized);
		self::assertSame($app, $restored->pubGetApplicationDirect(),
			'__wakeup() must re-adopt the global application singleton.');
	}

	public function testWakeupWithNoGlobalApplicationLeavesNull(): void
	{
		// If no application is registered at wakeup time, $_application stays null.
		$snap = PradoUnit::snapshotStatic(Prado::class, ['_application']);
		PradoUnit::setStaticProp(Prado::class, '_application', null);
		try {
			$serialized = serialize($this->comp);
			/** @var TApplicationComponentTest_Concrete $restored */
			$restored = unserialize($serialized);
			self::assertNull($restored->pubGetApplicationDirect(),
				'__wakeup() must not invent an application when none is registered.');
		} finally {
			PradoUnit::restoreStatic(Prado::class, $snap);
		}
	}
}
