<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TApplicationMode;
use Prado\TModule;

/**
 * A minimal concrete module for use in module-management tests.
 * TModule is abstract, so we need a concrete subclass.
 */
class AppTestModule extends TModule
{
	public function init($config) {}
}

/**
 * Exposes TApplication's protected methods as public wrappers for unit testing
 * without reflection method invocation at every call site.
 */
class TApplicationTestAccessor extends TApplication
{
	public function pubGetSteps(): array { return $this->getSteps(); }
	public function pubGetStep(): int { return $this->getStep(); }
	public function pubGetCacheFile(): ?string { return $this->getCacheFile(); }
	public function pubSetCacheFile(?string $v): void { $this->setCacheFile($v); }
	public function pubBuildCacheFilePath(string $path): string { return $this->buildCacheFilePath($path); }
	public function pubSetRuntimePathDirect(string $v): void { $this->setRuntimePathDirect($v); }
	public function pubGenerateAppUniqueId(string $token): string { return $this->generateAppUniqueId($token); }
	public function pubSetRequestCompleted(bool $v): void { $this->setRequestCompleted($v); }
	public function pubSetUniqueID(string $v): void { $this->setUniqueID($v); }
	public function pubHasLazyModule(string $id): bool { return $this->hasLazyModule($id); }
	public function pubGetLazyModule(string $id): ?array { return $this->getLazyModule($id); }
	public function pubSetLazyModule(string $id, ?array $config): void { $this->setLazyModule($id, $config); }
	public function pubGetLazyModuleCount(): int { return $this->getLazyModuleCount(); }
}

/**
 * Subclass that overrides DEFAULT_APPLICATION_MODE.
 * Used to verify that TApplication's constructor honours late-static-binding on the constant.
 */
class AppCustomMode extends TApplication
{
	public const DEFAULT_APPLICATION_MODE = TApplicationMode::Normal;
}

/**
 * Subclass that overrides DEFAULT_PAGE_SERVICE_CLASS.
 * Used to verify that TApplication's constructor honours late-static-binding on the constant.
 */
class AppCustomPageService extends TApplication
{
	public const DEFAULT_PAGE_SERVICE_CLASS = 'Prado\Web\Services\TJsonService';
}

/**
 * Subclass that overrides getSteps() to return a reduced lifecycle.
 */
class AppCustomSteps extends TApplication
{
	public const CUSTOM_STEPS = ['onBeginRequest', 'runService', 'flushOutput'];

	protected function getSteps(): array
	{
		return self::CUSTOM_STEPS;
	}
}

/**
 * A concrete subclass of AppTestModule used to exercise getModulesByType strict mode.
 */
class AppTestModuleSubclass extends AppTestModule {}

/**
 * A minimal IUser stub for setUser / onSetUser tests.
 */
class AppTestUser implements \Prado\Security\IUser
{
	public function getName() { return 'test'; }
	public function setName($value) {}
	public function getIsGuest() { return false; }
	public function setIsGuest($value) {}
	public function getRoles() { return []; }
	public function setRoles($value) {}
	public function isInRole($role) { return false; }
	public function saveToString() { return ''; }
	public function loadFromString($string) {}
}

/**
 * Comprehensive tests for TApplication methods outside the service registry.
 *
 * Covers: constants, singleton, ID / UniqueID, Mode, paths, config type,
 * global state, request-completion flag, module management, parameters,
 * all lazy-loaded module accessors, cache, user, globalization,
 * authorization rules, and the full set of lifecycle event methods.
 *
 * @package System
 */
class TApplicationTest extends PHPUnit\Framework\TestCase
{
	private TApplication $_app;

	/** Full property snapshot of the global app, captured in setUp. */
	private array $_snap = [];

	protected function setUp(): void
	{
		$this->_app = Prado::getApplication();
		$this->_snap = TTestApplication::snapshotApp($this->_app);
	}

	protected function tearDown(): void
	{
		TTestApplication::restoreApp($this->_snap, $this->_app);
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function testConstant_pageServiceId(): void
	{
		$this->assertSame('page', TApplication::PAGE_SERVICE_ID);
	}

	public function testConstant_configTypeXml(): void
	{
		$this->assertSame('xml', TApplication::CONFIG_TYPE_XML);
	}

	public function testConstant_configTypePhp(): void
	{
		$this->assertSame('php', TApplication::CONFIG_TYPE_PHP);
	}

	public function testConstant_configFileXml(): void
	{
		$this->assertSame('application.xml', TApplication::CONFIG_FILE_XML);
	}

	public function testConstant_configFilePhp(): void
	{
		$this->assertSame('application.php', TApplication::CONFIG_FILE_PHP);
	}

	public function testConstant_configFileExtXml(): void
	{
		$this->assertSame('.xml', TApplication::CONFIG_FILE_EXT_XML);
	}

	public function testConstant_configFileExtPhp(): void
	{
		$this->assertSame('.php', TApplication::CONFIG_FILE_EXT_PHP);
	}

	public function testConstant_runtimePath(): void
	{
		$this->assertSame('runtime', TApplication::RUNTIME_PATH);
	}

	public function testConstant_configCacheFile(): void
	{
		$this->assertSame('config.cache', TApplication::CONFIGCACHE_FILE);
	}

	public function testConstant_globalFile(): void
	{
		$this->assertSame('global.cache', TApplication::GLOBAL_FILE);
	}

	// -----------------------------------------------------------------------
	// singleton()
	// -----------------------------------------------------------------------

	public function testSingleton_returnsApplicationInstance(): void
	{
		$result = TApplication::singleton();
		$this->assertSame($this->_app, $result);
	}

	public function testSingleton_returnsSameObjectAsPradoGetApplication(): void
	{
		$this->assertSame(Prado::getApplication(), TApplication::singleton());
	}

	public function testSingleton_withCreateFalseStillReturnsInstance(): void
	{
		// The $create parameter is ignored; the singleton is always returned.
		$this->assertSame($this->_app, TApplication::singleton(false));
	}

	// -----------------------------------------------------------------------
	// ID
	// -----------------------------------------------------------------------

	public function testGetSetId_roundTrip(): void
	{
		$this->_app->setID('my-app-id');
		$this->assertSame('my-app-id', $this->_app->getID());
	}

	public function testGetId_acceptsEmptyString(): void
	{
		$this->_app->setID('');
		$this->assertSame('', $this->_app->getID());
	}

	// -----------------------------------------------------------------------
	// UniqueID
	// -----------------------------------------------------------------------

	public function testGetUniqueId_returnsNonEmptyString(): void
	{
		$uid = $this->_app->getUniqueID();
		$this->assertIsString($uid);
		$this->assertNotEmpty($uid);
	}

	public function testGetUniqueId_isMd5HashFormat(): void
	{
		$uid = $this->_app->getUniqueID();
		// md5 produces a 32-character hex string
		$this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $uid);
	}

	public function testGetUniqueId_changesWhenRuntimePathChanges(): void
	{
		$original = $this->_app->getUniqueID();
		$originalPath = $this->_app->getRuntimePath();

		$this->_app->setRuntimePath($originalPath . '/different');
		$newUid = $this->_app->getUniqueID();

		$this->assertNotSame($original, $newUid);
	}

	// -----------------------------------------------------------------------
	// Mode
	// -----------------------------------------------------------------------

	public function testGetMode_defaultIsDebug(): void
	{
		// The bootstrap app is created without an explicit mode; default is Debug.
		$this->assertSame(TApplicationMode::Debug, $this->_app->getMode());
	}

	public function testSetMode_debug(): void
	{
		$this->_app->setMode(TApplicationMode::Debug);
		$this->assertSame(TApplicationMode::Debug, $this->_app->getMode());
	}

	public function testSetMode_normal(): void
	{
		$this->_app->setMode(TApplicationMode::Normal);
		$this->assertSame(TApplicationMode::Normal, $this->_app->getMode());
	}

	public function testSetMode_performance(): void
	{
		$this->_app->setMode(TApplicationMode::Performance);
		$this->assertSame(TApplicationMode::Performance, $this->_app->getMode());
	}

	public function testSetMode_off(): void
	{
		$this->_app->setMode(TApplicationMode::Off);
		$this->assertSame(TApplicationMode::Off, $this->_app->getMode());
	}

	public function testSetMode_invalidValueThrows(): void
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$this->_app->setMode('InvalidMode');
	}

	// -----------------------------------------------------------------------
	// BasePath
	// -----------------------------------------------------------------------

	public function testGetSetBasePath_roundTrip(): void
	{
		$this->_app->setBasePath('/tmp/test-base');
		$this->assertSame('/tmp/test-base', $this->_app->getBasePath());
	}

	public function testGetBasePath_isNonEmptyString(): void
	{
		$this->assertIsString($this->_app->getBasePath());
		$this->assertNotEmpty($this->_app->getBasePath());
	}

	// -----------------------------------------------------------------------
	// RuntimePath
	// -----------------------------------------------------------------------

	public function testGetSetRuntimePath_roundTrip(): void
	{
		$this->_app->setRuntimePath('/tmp/test-runtime');
		$this->assertSame('/tmp/test-runtime', $this->_app->getRuntimePath());
	}

	public function testSetRuntimePath_updatesUniqueID(): void
	{
		$originalUid  = $this->_app->getUniqueID();
		$originalPath = $this->_app->getRuntimePath();

		$this->_app->setRuntimePath($originalPath . '/variant');
		$this->assertNotSame($originalUid, $this->_app->getUniqueID());
		$this->assertSame(md5($originalPath . '/variant'), $this->_app->getUniqueID());
	}

	public function testSetRuntimePath_updatesCacheFileWhenOneIsSet(): void
	{
		// Prime _cacheFile to a non-null value to activate the rebuild branch.
		PradoUnit::setProp($this->_app, '_cacheFile', '/old/path/config.cache');

		$newRuntime = $this->_app->getRuntimePath() . '/variant';
		$this->_app->setRuntimePath($newRuntime);

		$expected = $newRuntime . DIRECTORY_SEPARATOR . TApplication::CONFIGCACHE_FILE;
		$this->assertSame($expected, PradoUnit::getProp($this->_app, '_cacheFile'));
	}

	public function testSetRuntimePath_doesNotUpdateCacheFileWhenNoCacheFile(): void
	{
		PradoUnit::setProp($this->_app, '_cacheFile', null);
		$this->_app->setRuntimePath($this->_app->getRuntimePath() . '/variant');
		$this->assertNull(PradoUnit::getProp($this->_app, '_cacheFile'));
	}

	// -----------------------------------------------------------------------
	// ConfigurationType / ConfigurationFileExt / ConfigurationFileName
	// -----------------------------------------------------------------------

	public function testGetSetConfigurationType_roundTrip(): void
	{
		$this->_app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$this->assertSame(TApplication::CONFIG_TYPE_PHP, $this->_app->getConfigurationType());
	}

	public function testGetConfigurationFileExt_xmlType(): void
	{
		// Reset the cached ext value so it re-evaluates from the type.
		PradoUnit::setProp($this->_app, '_configFileExt', null);
		$this->_app->setConfigurationType(TApplication::CONFIG_TYPE_XML);

		$this->assertSame(TApplication::CONFIG_FILE_EXT_XML, $this->_app->getConfigurationFileExt());
	}

	public function testGetConfigurationFileExt_phpType(): void
	{
		PradoUnit::setProp($this->_app, '_configFileExt', null);
		$this->_app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);

		$this->assertSame(TApplication::CONFIG_FILE_EXT_PHP, $this->_app->getConfigurationFileExt());
		// restore
		PradoUnit::setProp($this->_app, '_configFileExt', null);
		$this->_app->setConfigurationType(TApplication::CONFIG_TYPE_XML);
	}

	public function testGetConfigurationFileExt_cachedOnSecondCall(): void
	{
		PradoUnit::setProp($this->_app, '_configFileExt', null);
		$this->_app->setConfigurationType(TApplication::CONFIG_TYPE_XML);

		$first  = $this->_app->getConfigurationFileExt();
		$second = $this->_app->getConfigurationFileExt();
		$this->assertSame($first, $second);
	}

	public function testGetConfigurationFile_returnsStringOrNull(): void
	{
		$cf = $this->_app->getConfigurationFile();
		$this->assertTrue($cf === null || is_string($cf));
	}

	public function testSetGetConfigurationFile_roundTrip(): void
	{
		$this->_app->setConfigurationFile('/some/path/application.xml');
		$this->assertSame('/some/path/application.xml', $this->_app->getConfigurationFile());
	}

	// -----------------------------------------------------------------------
	// GlobalState
	// -----------------------------------------------------------------------

	/**
	 * Regression: loadGlobals() assigns the raw return value of
	 * IStatePersister::load() to $_globals. When the persister has no stored
	 * data it returns null, leaving $_globals = null. Any subsequent call that
	 * passes $_globals to array_key_exists() (getGlobalState, setGlobalState,
	 * clearGlobalState) then throws a TypeError because array_key_exists()
	 * requires its second argument to be an array.
	 *
	 * The fix is: $this->_globals = $this->getApplicationStatePersister()->load() ?? [];
	 *
	 * This test will FAIL (TypeError) until that fix is applied.
	 */
	public function testLoadGlobals_whenPersisterReturnsNull_getGlobalStateReturnsDefault(): void
	{
		$mockPersister = $this->createMock(\Prado\IStatePersister::class);
		$mockPersister->method('load')->willReturn(null);
		$this->_app->setApplicationStatePersister($mockPersister);

		$this->_app->onLoadState();

		// Without "?? []" this throws TypeError: array_key_exists(): Argument #2
		// ($array) must be of type array, null given.
		$this->assertSame('default', $this->_app->getGlobalState('any-key', 'default'));
	}

	public function testGetGlobalState_returnsDefaultWhenMissing(): void
	{
		PradoUnit::setProp($this->_app, '_globals', []);
		$this->assertNull($this->_app->getGlobalState('missing'));
		$this->assertSame('default', $this->_app->getGlobalState('missing', 'default'));
	}

	public function testSetGlobalState_storesValue(): void
	{
		PradoUnit::setProp($this->_app, '_globals', []);
		$this->_app->setGlobalState('key', 'value');
		$this->assertSame('value', $this->_app->getGlobalState('key'));
	}

	public function testSetGlobalState_marksStateChanged(): void
	{
		PradoUnit::setProp($this->_app, '_stateChanged', false);
		$this->_app->setGlobalState('x', 'y');
		$this->assertTrue(PradoUnit::getProp($this->_app, '_stateChanged'));
	}

	public function testSetGlobalState_valueEqualsDefaultRemovesKey(): void
	{
		PradoUnit::setProp($this->_app, '_globals', ['k' => 'v']);
		// When value === defaultValue the entry must be removed.
		$this->_app->setGlobalState('k', 'sentinel', 'sentinel');
		$globals = PradoUnit::getProp($this->_app, '_globals');
		$this->assertArrayNotHasKey('k', $globals);
	}

	public function testSetGlobalState_overwritesExistingKey(): void
	{
		PradoUnit::setProp($this->_app, '_globals', []);
		$this->_app->setGlobalState('k', 'first');
		$this->_app->setGlobalState('k', 'second');
		$this->assertSame('second', $this->_app->getGlobalState('k'));
	}

	public function testClearGlobalState_removesKey(): void
	{
		PradoUnit::setProp($this->_app, '_globals', ['toClear' => 42]);
		$this->_app->clearGlobalState('toClear');
		$this->assertNull($this->_app->getGlobalState('toClear'));
	}

	public function testClearGlobalState_marksStateChanged(): void
	{
		PradoUnit::setProp($this->_app, '_globals', ['k' => 'v']);
		PradoUnit::setProp($this->_app, '_stateChanged', false);
		$this->_app->clearGlobalState('k');
		$this->assertTrue(PradoUnit::getProp($this->_app, '_stateChanged'));
	}

	public function testClearGlobalState_nonExistentKeyIsNoOp(): void
	{
		PradoUnit::setProp($this->_app, '_globals', ['other' => 1]);
		$this->_app->clearGlobalState('ghost');
		$this->assertSame(1, $this->_app->getGlobalState('other'));
	}

	public function testClearGlobalState_nonExistentKey_stateChangedStaysFalse(): void
	{
		PradoUnit::setProp($this->_app, '_globals', []);
		PradoUnit::setProp($this->_app, '_stateChanged', false);
		$this->_app->clearGlobalState('ghost');
		$this->assertFalse(PradoUnit::getProp($this->_app, '_stateChanged'));
	}

	public function testSetGlobalState_identicalValueIsNoOp_stateChangedStaysFalse(): void
	{
		PradoUnit::setProp($this->_app, '_globals', ['k' => 'existing']);
		PradoUnit::setProp($this->_app, '_stateChanged', false);
		// Same value — must not mark state as changed.
		$this->_app->setGlobalState('k', 'existing');
		$this->assertFalse(PradoUnit::getProp($this->_app, '_stateChanged'));
	}

	public function testSetGlobalState_forceSave_callsSaveGlobalsImmediately(): void
	{
		PradoUnit::setProp($this->_app, '_globals', []);
		PradoUnit::setProp($this->_app, '_stateChanged', false);

		$mockPersister = $this->createMock(\Prado\IStatePersister::class);
		$mockPersister->expects($this->once())->method('save');
		$this->_app->setApplicationStatePersister($mockPersister);

		// forceSave=true with a new key: _stateChanged becomes true, then saveGlobals() is called.
		$this->_app->setGlobalState('k', 'v', null, true);
	}

	public function testSetGlobalState_forceSaveWithIdenticalValue_doesNotSave(): void
	{
		PradoUnit::setProp($this->_app, '_globals', ['k' => 'same']);
		PradoUnit::setProp($this->_app, '_stateChanged', false);

		$mockPersister = $this->createMock(\Prado\IStatePersister::class);
		$mockPersister->expects($this->never())->method('save');
		$this->_app->setApplicationStatePersister($mockPersister);

		// forceSave=true but value unchanged: _stateChanged stays false, saveGlobals() skips the save.
		$this->_app->setGlobalState('k', 'same', null, true);
	}

	// -----------------------------------------------------------------------
	// onGlobalStateChange event
	// -----------------------------------------------------------------------

	public function testSetGlobalState_newKey_raisesEvent_withCorrectPayload(): void
	{
		PradoUnit::setProp($this->_app, '_globals', []);
		$captured = null;
		$this->_app->attachEventHandler('onGlobalStateChange', function ($sender, $param) use (&$captured) {
			$captured = $param;
		});

		$this->_app->setGlobalState('myKey', 'myValue');

		$this->assertNotNull($captured);
		$this->assertSame('myKey', $captured['key']);
		$this->assertSame('myValue', $captured['value']);
		$this->assertFalse($captured['isDefault']);
		// new key — oldValue must be absent
		$this->assertFalse(isset($captured['oldValue']));
	}

	public function testSetGlobalState_existingKey_raisesEvent_withOldValue(): void
	{
		PradoUnit::setProp($this->_app, '_globals', ['k' => 'before']);
		$captured = null;
		$this->_app->attachEventHandler('onGlobalStateChange', function ($sender, $param) use (&$captured) {
			$captured = $param;
		});

		$this->_app->setGlobalState('k', 'after');

		// overwriting an existing key — oldValue must be present
		$this->assertTrue(isset($captured['oldValue']));
		$this->assertSame('before', $captured['oldValue']);
		$this->assertSame('after', $captured['value']);
		$this->assertFalse($captured['isDefault']);
	}

	public function testSetGlobalState_clearedToDefault_raisesEvent_isDefaultTrue(): void
	{
		PradoUnit::setProp($this->_app, '_globals', ['k' => 'existing']);
		$captured = null;
		$this->_app->attachEventHandler('onGlobalStateChange', function ($sender, $param) use (&$captured) {
			$captured = $param;
		});

		// setGlobalState clears to default — 'value' is present (the passed value),
		// 'unset' is absent (only set by clearGlobalState),
		// 'oldValue' is present because the key existed before.
		$this->_app->setGlobalState('k', 'sentinel', 'sentinel');

		$this->assertTrue($captured['isDefault']);
		$this->assertSame('sentinel', $captured['value']);
		$this->assertFalse(isset($captured['unset']));
		$this->assertTrue(isset($captured['oldValue']));
		$this->assertSame('existing', $captured['oldValue']);
	}

	public function testSetGlobalState_identicalValue_doesNotRaiseEvent(): void
	{
		PradoUnit::setProp($this->_app, '_globals', ['k' => 'same']);
		$called = false;
		$this->_app->attachEventHandler('onGlobalStateChange', function () use (&$called) {
			$called = true;
		});

		$this->_app->setGlobalState('k', 'same');

		$this->assertFalse($called);
	}

	public function testSetGlobalState_defaultKeyNotPresent_doesNotRaiseEvent(): void
	{
		PradoUnit::setProp($this->_app, '_globals', []);
		$called = false;
		$this->_app->attachEventHandler('onGlobalStateChange', function () use (&$called) {
			$called = true;
		});

		// value === defaultValue and key not present — no actual change
		$this->_app->setGlobalState('missing', 'sentinel', 'sentinel');

		$this->assertFalse($called);
	}

	public function testClearGlobalState_raisesEvent_withCorrectPayload(): void
	{
		PradoUnit::setProp($this->_app, '_globals', ['k' => 'old']);
		$captured = null;
		$this->_app->attachEventHandler('onGlobalStateChange', function ($sender, $param) use (&$captured) {
			$captured = $param;
		});

		$this->_app->clearGlobalState('k');

		$this->assertNotNull($captured);
		$this->assertSame('k', $captured['key']);
		$this->assertTrue($captured['unset']);
		$this->assertFalse(isset($captured['value']));
		$this->assertFalse(isset($captured['isDefault']));
		$this->assertSame('old', $captured['oldValue']);
	}

	public function testClearGlobalState_nonExistentKey_doesNotRaiseEvent(): void
	{
		PradoUnit::setProp($this->_app, '_globals', []);
		$called = false;
		$this->_app->attachEventHandler('onGlobalStateChange', function () use (&$called) {
			$called = true;
		});

		$this->_app->clearGlobalState('ghost');

		$this->assertFalse($called);
	}

	public function testOnGlobalStateChange_param_isReadOnly(): void
	{
		PradoUnit::setProp($this->_app, '_globals', []);
		$captured = null;
		$this->_app->attachEventHandler('onGlobalStateChange', function ($sender, $param) use (&$captured) {
			$captured = $param;
		});

		$this->_app->setGlobalState('k', 'v');

		$this->assertTrue($captured->getReadOnly());
	}

	public function testOnGlobalStateChange_param_isReadOnly_viaClear(): void
	{
		PradoUnit::setProp($this->_app, '_globals', ['k' => 'v']);
		$captured = null;
		$this->_app->attachEventHandler('onGlobalStateChange', function ($sender, $param) use (&$captured) {
			$captured = $param;
		});

		$this->_app->clearGlobalState('k');

		$this->assertTrue($captured->getReadOnly());
	}

	public function testOnGlobalStateChange_readOnlyParam_throwsOnMutation(): void
	{
		PradoUnit::setProp($this->_app, '_globals', []);
		$captured = null;
		$this->_app->attachEventHandler('onGlobalStateChange', function ($sender, $param) use (&$captured) {
			$captured = $param;
		});

		$this->_app->setGlobalState('k', 'v');

		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$captured['key'] = 'mutated';
	}

	public function testSetGlobalState_senderIsApplication(): void
	{
		PradoUnit::setProp($this->_app, '_globals', []);
		$capturedSender = null;
		$this->_app->attachEventHandler('onGlobalStateChange', function ($sender, $param) use (&$capturedSender) {
			$capturedSender = $sender;
		});

		$this->_app->setGlobalState('k', 'v');

		$this->assertSame($this->_app, $capturedSender);
	}

	public function testClearGlobalState_senderIsApplication(): void
	{
		PradoUnit::setProp($this->_app, '_globals', ['k' => 'v']);
		$capturedSender = null;
		$this->_app->attachEventHandler('onGlobalStateChange', function ($sender, $param) use (&$capturedSender) {
			$capturedSender = $sender;
		});

		$this->_app->clearGlobalState('k');

		$this->assertSame($this->_app, $capturedSender);
	}

	public function testSetGlobalState_existingKey_payloadContainsKey(): void
	{
		PradoUnit::setProp($this->_app, '_globals', ['myKey' => 'old']);
		$captured = null;
		$this->_app->attachEventHandler('onGlobalStateChange', function ($sender, $param) use (&$captured) {
			$captured = $param;
		});

		$this->_app->setGlobalState('myKey', 'new');

		$this->assertSame('myKey', $captured['key']);
	}

	public function testSetGlobalState_clearedToDefault_payloadContainsKey(): void
	{
		PradoUnit::setProp($this->_app, '_globals', ['myKey' => 'existing']);
		$captured = null;
		$this->_app->attachEventHandler('onGlobalStateChange', function ($sender, $param) use (&$captured) {
			$captured = $param;
		});

		$this->_app->setGlobalState('myKey', 'x', 'x');

		$this->assertSame('myKey', $captured['key']);
	}

	public function testSetGlobalState_eventFiresAfterMutation_newValueVisible(): void
	{
		PradoUnit::setProp($this->_app, '_globals', []);
		$observedInHandler = 'not-set';
		$this->_app->attachEventHandler('onGlobalStateChange', function ($sender, $param) use (&$observedInHandler) {
			$observedInHandler = $sender->getGlobalState($param['key']);
		});

		$this->_app->setGlobalState('k', 'expected');

		$this->assertSame('expected', $observedInHandler);
	}

	public function testSetGlobalState_clearedToDefault_eventFiresAfterMutation_keyAbsent(): void
	{
		PradoUnit::setProp($this->_app, '_globals', ['k' => 'old']);
		$keyExistsInHandler = true;
		$this->_app->attachEventHandler('onGlobalStateChange', function ($sender, $param) use (&$keyExistsInHandler) {
			// After clearing to default, getGlobalState returns its default (null).
			$keyExistsInHandler = ($sender->getGlobalState($param['key'], 'missing') !== 'missing');
		});

		$this->_app->setGlobalState('k', 'x', 'x');

		$this->assertFalse($keyExistsInHandler);
	}

	public function testClearGlobalState_eventFiresAfterMutation_keyAbsent(): void
	{
		PradoUnit::setProp($this->_app, '_globals', ['k' => 'old']);
		$keyExistsInHandler = true;
		$this->_app->attachEventHandler('onGlobalStateChange', function ($sender, $param) use (&$keyExistsInHandler) {
			$keyExistsInHandler = ($sender->getGlobalState($param['key'], 'missing') !== 'missing');
		});

		$this->_app->clearGlobalState('k');

		$this->assertFalse($keyExistsInHandler);
	}

	public function testSetGlobalState_forceSave_eventAlsoFires(): void
	{
		PradoUnit::setProp($this->_app, '_globals', []);
		$mockPersister = $this->createMock(\Prado\IStatePersister::class);
		$mockPersister->expects($this->once())->method('save');
		$this->_app->setApplicationStatePersister($mockPersister);

		$called = false;
		$this->_app->attachEventHandler('onGlobalStateChange', function () use (&$called) {
			$called = true;
		});

		$this->_app->setGlobalState('k', 'v', null, true);

		$this->assertTrue($called);
	}

	public function testOnGlobalStateChange_directCall_raisesEvent(): void
	{
		$called = false;
		$this->_app->attachEventHandler('onGlobalStateChange', function () use (&$called) {
			$called = true;
		});

		$this->_app->onGlobalStateChange(new \Prado\TEventParameter());

		$this->assertTrue($called);
	}

	public function testSetGlobalState_nullValue_storedAndDetectedCorrectly(): void
	{
		// null can be stored as a non-default value when $defaultValue is not null.
		PradoUnit::setProp($this->_app, '_globals', []);
		$this->_app->setGlobalState('k', null, false);

		$this->assertSame(null, $this->_app->getGlobalState('k', 'missing'));
	}

	public function testSetGlobalState_nullValue_identicalCallIsNoOp(): void
	{
		// After storing null, setting it again to null must not fire the event.
		PradoUnit::setProp($this->_app, '_globals', []);
		$this->_app->setGlobalState('k', null, false);

		$called = false;
		$this->_app->attachEventHandler('onGlobalStateChange', function () use (&$called) {
			$called = true;
		});

		$this->_app->setGlobalState('k', null, false);

		$this->assertFalse($called);
	}

	public function testGetGlobalState_returnsNullValue_notDefault(): void
	{
		// getGlobalState must return a stored null, not fall through to $defaultValue.
		PradoUnit::setProp($this->_app, '_globals', ['k' => null]);

		$result = $this->_app->getGlobalState('k', 'default');

		$this->assertNull($result);
	}

	// -----------------------------------------------------------------------
	// completeRequest / getRequestCompleted
	// -----------------------------------------------------------------------

	public function testGetRequestCompleted_initiallyFalse(): void
	{
		PradoUnit::setProp($this->_app, '_requestCompleted', false);
		$this->assertFalse($this->_app->getRequestCompleted());
	}

	public function testCompleteRequest_setsCompletedFlag(): void
	{
		PradoUnit::setProp($this->_app, '_requestCompleted', false);
		$this->_app->completeRequest();
		$this->assertTrue($this->_app->getRequestCompleted());
	}

	public function testGetRequestCompleted_trueAfterComplete(): void
	{
		PradoUnit::setProp($this->_app, '_requestCompleted', false);
		$this->_app->completeRequest();
		$this->assertTrue($this->_app->getRequestCompleted());
	}

	// -----------------------------------------------------------------------
	// Module management
	// -----------------------------------------------------------------------

	public function testSetModule_registersModule(): void
	{
		$module = new AppTestModule();
		$this->_app->setModule('testmod_register', $module);
		$this->assertSame($module, $this->_app->getModule('testmod_register'));
	}

	public function testSetModule_allowsNullForLazyPlaceholder(): void
	{
		// setModule(id, null) is used internally to register a lazy-load slot.
		$this->_app->setModule('testmod_lazy', null);
		// getModules() should have the key present (even though value is null).
		$this->assertArrayHasKey('testmod_lazy', $this->_app->getModules());
	}

	public function testSetModule_duplicateIdThrows(): void
	{
		$module = new AppTestModule();
		$this->_app->setModule('testmod_dup', $module);
		$this->expectException(TConfigurationException::class);
		$this->_app->setModule('testmod_dup', new AppTestModule());
	}

	public function testGetModule_returnsNullForUnregisteredId(): void
	{
		$this->assertNull($this->_app->getModule('does_not_exist_xyz'));
	}

	public function testGetModules_returnsArray(): void
	{
		$this->assertIsArray($this->_app->getModules());
	}

	public function testGetModules_containsRegisteredModule(): void
	{
		$module = new AppTestModule();
		$this->_app->setModule('testmod_in_map', $module);
		$this->assertArrayHasKey('testmod_in_map', $this->_app->getModules());
		$this->assertSame($module, $this->_app->getModules()['testmod_in_map']);
	}

	// -----------------------------------------------------------------------
	// Parameters
	// -----------------------------------------------------------------------

	public function testGetParameters_returnsTMap(): void
	{
		$params = $this->_app->getParameters();
		$this->assertInstanceOf(\Prado\Collections\TMap::class, $params);
	}

	public function testGetParameters_sameTMapOnSubsequentCalls(): void
	{
		$this->assertSame($this->_app->getParameters(), $this->_app->getParameters());
	}

	// -----------------------------------------------------------------------
	// Lazy-loaded module accessors
	// -----------------------------------------------------------------------

	public function testGetRequest_returnsHttpRequest(): void
	{
		$this->assertInstanceOf(\Prado\Web\THttpRequest::class, $this->_app->getRequest());
	}

	public function testSetGetRequest_roundTrip(): void
	{
		$req = new \Prado\Web\THttpRequest();
		$this->_app->setRequest($req);
		$this->assertSame($req, $this->_app->getRequest());
	}

	public function testGetResponse_returnsHttpResponse(): void
	{
		// Force the lazy path so auto-creation is exercised.
		PradoUnit::setProp($this->_app, '_response', null);
		$levelBefore = ob_get_level();
		$this->assertInstanceOf(\Prado\Web\THttpResponse::class, $this->_app->getResponse());
		// THttpResponse::init() calls ob_start() when BufferOutput is true.
		// Close only the extra buffer(s) our code opened to satisfy PHPUnit.
		while (ob_get_level() > $levelBefore) {
			ob_end_clean();
		}
	}

	public function testSetGetResponse_roundTrip(): void
	{
		$resp = new \Prado\Web\THttpResponse();
		$this->_app->setResponse($resp);
		$this->assertSame($resp, $this->_app->getResponse());
	}

	public function testGetSession_returnsHttpSession(): void
	{
		$this->assertInstanceOf(\Prado\Web\THttpSession::class, $this->_app->getSession());
	}

	public function testSetGetSession_roundTrip(): void
	{
		$sess = new \Prado\Web\THttpSession();
		$this->_app->setSession($sess);
		$this->assertSame($sess, $this->_app->getSession());
	}

	public function testGetSecurityManager_returnsSecurityManager(): void
	{
		$this->assertInstanceOf(\Prado\Security\TSecurityManager::class, $this->_app->getSecurityManager());
	}

	public function testSetGetSecurityManager_roundTrip(): void
	{
		$sm = new \Prado\Security\TSecurityManager();
		$this->_app->setSecurityManager($sm);
		$this->assertSame($sm, $this->_app->getSecurityManager());
	}

	public function testGetErrorHandler_returnsErrorHandler(): void
	{
		$this->assertInstanceOf(\Prado\Exceptions\TErrorHandler::class, $this->_app->getErrorHandler());
	}

	public function testSetGetErrorHandler_roundTrip(): void
	{
		$eh = new \Prado\Exceptions\TErrorHandler();
		$this->_app->setErrorHandler($eh);
		$this->assertSame($eh, $this->_app->getErrorHandler());
	}

	public function testGetAssetManager_returnsAssetManager(): void
	{
		// TAssetManager::init() derives its BasePath from the web document root,
		// which does not exist in the CLI unit-test environment. Test the lazy-load
		// contract via the setter so that init() is never invoked here.
		$am = new \Prado\Web\TAssetManager();
		$this->_app->setAssetManager($am);
		$this->assertInstanceOf(\Prado\Web\TAssetManager::class, $this->_app->getAssetManager());
	}

	public function testSetGetAssetManager_roundTrip(): void
	{
		$first  = new \Prado\Web\TAssetManager();
		$second = new \Prado\Web\TAssetManager();
		$this->_app->setAssetManager($first);
		$this->assertSame($first, $this->_app->getAssetManager());
		$this->_app->setAssetManager($second);
		$this->assertSame($second, $this->_app->getAssetManager());
	}

	public function testGetTemplateManager_returnsTemplateManager(): void
	{
		$this->assertInstanceOf(\Prado\Web\UI\TTemplateManager::class, $this->_app->getTemplateManager());
	}

	public function testSetGetTemplateManager_roundTrip(): void
	{
		$tm = new \Prado\Web\UI\TTemplateManager();
		$this->_app->setTemplateManager($tm);
		$this->assertSame($tm, $this->_app->getTemplateManager());
	}

	public function testGetThemeManager_returnsThemeManager(): void
	{
		$this->assertInstanceOf(\Prado\Web\UI\TThemeManager::class, $this->_app->getThemeManager());
	}

	public function testSetGetThemeManager_roundTrip(): void
	{
		$thm = new \Prado\Web\UI\TThemeManager();
		$this->_app->setThemeManager($thm);
		$this->assertSame($thm, $this->_app->getThemeManager());
	}

	// -----------------------------------------------------------------------
	// Cache
	// -----------------------------------------------------------------------

	public function testGetCache_nullByDefault(): void
	{
		PradoUnit::setProp($this->_app, '_cache', null);
		$this->assertNull($this->_app->getCache());
	}

	public function testSetGetCache_roundTrip(): void
	{
		$cacheMock = $this->createMock(\Prado\Caching\ICache::class);
		$this->_app->setCache($cacheMock);
		$this->assertSame($cacheMock, $this->_app->getCache());
	}

	// -----------------------------------------------------------------------
	// User
	// -----------------------------------------------------------------------

	public function testGetUser_nullByDefault(): void
	{
		PradoUnit::setProp($this->_app, '_user', null);
		$this->assertNull($this->_app->getUser());
	}

	public function testSetUser_storesUser(): void
	{
		$user = new AppTestUser();
		$this->_app->setUser($user);
		$this->assertSame($user, $this->_app->getUser());
	}

	public function testSetUser_raisesOnSetUserEvent(): void
	{
		$receivedUser = null;
		$handler = function ($sender, $param) use (&$receivedUser) {
			$receivedUser = $param;
		};
		$this->_app->attachEventHandler('onSetUser', $handler);

		$user = new AppTestUser();
		$this->_app->setUser($user);

		$this->_app->detachEventHandler('onSetUser', $handler);
		$this->assertSame($user, $receivedUser);
	}

	public function testOnSetUser_raisesEventDirectly(): void
	{
		$received = null;
		$handler  = function ($sender, $param) use (&$received) { $received = $param; };
		$this->_app->attachEventHandler('onSetUser', $handler);

		$user = new AppTestUser();
		$this->_app->onSetUser($user);

		$this->_app->detachEventHandler('onSetUser', $handler);
		$this->assertSame($user, $received);
	}

	// -----------------------------------------------------------------------
	// Globalization
	// -----------------------------------------------------------------------

	public function testGetGlobalization_createIfNotExists_true(): void
	{
		PradoUnit::setProp($this->_app, '_globalization', null);
		$glob = $this->_app->getGlobalization(true);
		$this->assertInstanceOf(\Prado\I18N\TGlobalization::class, $glob);
	}

	public function testGetGlobalization_createIfNotExists_false_returnsNull(): void
	{
		PradoUnit::setProp($this->_app, '_globalization', null);
		$this->assertNull($this->_app->getGlobalization(false));
	}

	public function testGetGlobalization_defaultParameterCreates(): void
	{
		PradoUnit::setProp($this->_app, '_globalization', null);
		$glob = $this->_app->getGlobalization();
		$this->assertInstanceOf(\Prado\I18N\TGlobalization::class, $glob);
	}

	public function testSetGetGlobalization_roundTrip(): void
	{
		$glob = new \Prado\I18N\TGlobalization();
		$this->_app->setGlobalization($glob);
		$this->assertSame($glob, $this->_app->getGlobalization(false));
	}

	// -----------------------------------------------------------------------
	// Authorization rules
	// -----------------------------------------------------------------------

	public function testGetAuthorizationRules_returnsCollection(): void
	{
		PradoUnit::setProp($this->_app, '_authRules', null);
		$rules = $this->_app->getAuthorizationRules();
		$this->assertInstanceOf(\Prado\Security\TAuthorizationRuleCollection::class, $rules);
	}

	public function testGetAuthorizationRules_samInstanceOnSubsequentCalls(): void
	{
		PradoUnit::setProp($this->_app, '_authRules', null);
		$first  = $this->_app->getAuthorizationRules();
		$second = $this->_app->getAuthorizationRules();
		$this->assertSame($first, $second);
	}

	// -----------------------------------------------------------------------
	// ApplicationStatePersister
	// -----------------------------------------------------------------------

	public function testGetApplicationStatePersister_returnsStatePersister(): void
	{
		PradoUnit::setProp($this->_app, '_statePersister', null);
		$persister = $this->_app->getApplicationStatePersister();
		$this->assertInstanceOf(\Prado\IStatePersister::class, $persister);
	}

	public function testSetGetApplicationStatePersister_roundTrip(): void
	{
		$mock = $this->createMock(\Prado\IStatePersister::class);
		$this->_app->setApplicationStatePersister($mock);
		$this->assertSame($mock, $this->_app->getApplicationStatePersister());
	}

	// -----------------------------------------------------------------------
	// Lifecycle events — each must raise the event with ($app, null) params
	// -----------------------------------------------------------------------

	public function testOnConfiguration_raisesEvent(): void
	{
		$this->assertEventRaisedWithAppAndNull(
			'onConfiguration',
			fn($app) => $app->onConfiguration()
		);
	}

	public function testOnInitComplete_raisesEvent(): void
	{
		$this->assertEventRaisedWithAppAndNull(
			'onInitComplete',
			fn($app) => $app->onInitComplete()
		);
	}

	private function assertEventRaisedWithAppAndNull(string $eventName, callable $trigger): void
	{
		$receivedSender = 'not-set';
		$receivedParam  = 'not-set';
		$handler = function ($sender, $param) use (&$receivedSender, &$receivedParam) {
			$receivedSender = $sender;
			$receivedParam  = $param;
		};
		$this->_app->attachEventHandler($eventName, $handler);

		$trigger($this->_app);

		$this->_app->detachEventHandler($eventName, $handler);
		$this->assertSame($this->_app, $receivedSender, "Expected sender to be the app in $eventName");
		$this->assertNull($receivedParam, "Expected null param in $eventName");
	}

	public function testOnBeginRequest_raisesEvent(): void
	{
		$this->assertEventRaisedWithAppAndNull(
			'OnBeginRequest',
			fn($app) => $app->onBeginRequest()
		);
	}

	public function testOnAuthentication_raisesEvent(): void
	{
		$this->assertEventRaisedWithAppAndNull(
			'OnAuthentication',
			fn($app) => $app->onAuthentication()
		);
	}

	public function testOnAuthenticationComplete_raisesEvent(): void
	{
		$this->assertEventRaisedWithAppAndNull(
			'OnAuthenticationComplete',
			fn($app) => $app->onAuthenticationComplete()
		);
	}

	public function testOnAuthorization_raisesEvent(): void
	{
		$this->assertEventRaisedWithAppAndNull(
			'OnAuthorization',
			fn($app) => $app->onAuthorization()
		);
	}

	public function testOnAuthorizationComplete_raisesEvent(): void
	{
		$this->assertEventRaisedWithAppAndNull(
			'OnAuthorizationComplete',
			fn($app) => $app->onAuthorizationComplete()
		);
	}

	public function testOnLoadStateComplete_raisesEvent(): void
	{
		$this->assertEventRaisedWithAppAndNull(
			'OnLoadStateComplete',
			fn($app) => $app->onLoadStateComplete()
		);
	}

	public function testOnPreRunService_raisesEvent(): void
	{
		$this->assertEventRaisedWithAppAndNull(
			'OnPreRunService',
			fn($app) => $app->onPreRunService()
		);
	}

	public function testOnSaveStateComplete_raisesEvent(): void
	{
		$this->assertEventRaisedWithAppAndNull(
			'OnSaveStateComplete',
			fn($app) => $app->onSaveStateComplete()
		);
	}

	public function testOnPreFlushOutput_raisesEvent(): void
	{
		$this->assertEventRaisedWithAppAndNull(
			'OnPreFlushOutput',
			fn($app) => $app->onPreFlushOutput()
		);
	}

	public function testOnLoadState_raisesEvent(): void
	{
		// onLoadState also calls loadGlobals(); provide a mock persister so
		// no file I/O occurs during this event test.
		$mockPersister = $this->createMock(\Prado\IStatePersister::class);
		$mockPersister->method('load')->willReturn([]);
		$this->_app->setApplicationStatePersister($mockPersister);

		$this->assertEventRaisedWithAppAndNull(
			'OnLoadState',
			fn($app) => $app->onLoadState()
		);
	}

	public function testOnSaveState_raisesEvent(): void
	{
		// onSaveState calls saveGlobals(); no globals have changed in this test,
		// so no I/O occurs, but we still inject a mock to be safe.
		$mockPersister = $this->createMock(\Prado\IStatePersister::class);
		$mockPersister->method('load')->willReturn([]);
		$this->_app->setApplicationStatePersister($mockPersister);
		PradoUnit::setProp($this->_app, '_stateChanged', false);

		$this->assertEventRaisedWithAppAndNull(
			'OnSaveState',
			fn($app) => $app->onSaveState()
		);
	}

	public function testOnLoadState_loadsGlobalsFromPersister(): void
	{
		$globalData = ['myKey' => 'myValue'];
		$mockPersister = $this->createMock(\Prado\IStatePersister::class);
		$mockPersister->method('load')->willReturn($globalData);
		$this->_app->setApplicationStatePersister($mockPersister);

		$this->_app->onLoadState();

		$this->assertSame('myValue', $this->_app->getGlobalState('myKey'));
	}

	public function testOnSaveState_savesGlobalsWhenStateChanged(): void
	{
		PradoUnit::setProp($this->_app, '_stateChanged', true);
		PradoUnit::setProp($this->_app, '_globals', ['k' => 'v']);

		$mockPersister = $this->createMock(\Prado\IStatePersister::class);
		$mockPersister->expects($this->once())
			->method('save')
			->with(['k' => 'v']);
		$this->_app->setApplicationStatePersister($mockPersister);

		$this->_app->onSaveState();
		// After saving, _stateChanged must be reset to false.
		$this->assertFalse(PradoUnit::getProp($this->_app, '_stateChanged'));
	}

	public function testOnSaveState_doesNotSaveWhenStateUnchanged(): void
	{
		PradoUnit::setProp($this->_app, '_stateChanged', false);

		$mockPersister = $this->createMock(\Prado\IStatePersister::class);
		$mockPersister->expects($this->never())->method('save');
		$this->_app->setApplicationStatePersister($mockPersister);

		$this->_app->onSaveState();
	}

	public function testOnSaveState_raisesEventBeforeSavingGlobals(): void
	{
		$callOrder = [];
		PradoUnit::setProp($this->_app, '_stateChanged', true);
		PradoUnit::setProp($this->_app, '_globals', ['k' => 'v']);

		$mockPersister = $this->createMock(\Prado\IStatePersister::class);
		$mockPersister->method('save')->willReturnCallback(function () use (&$callOrder) {
			$callOrder[] = 'save';
		});
		$this->_app->setApplicationStatePersister($mockPersister);

		$handler = function () use (&$callOrder) { $callOrder[] = 'event'; };
		$this->_app->attachEventHandler('OnSaveState', $handler);

		$this->_app->onSaveState();

		$this->_app->detachEventHandler('OnSaveState', $handler);
		$this->assertSame(['event', 'save'], $callOrder);
	}

	// -----------------------------------------------------------------------
	// getModulesByType
	// -----------------------------------------------------------------------

	public function testGetModulesByType_emptyModules_returnsEmptyArray(): void
	{
		PradoUnit::setProp($this->_app, '_modules', []);
		PradoUnit::setProp($this->_app, '_lazyModules', []);
		$this->assertSame([], $this->_app->getModulesByType(AppTestModule::class));
	}

	public function testGetModulesByType_returnsMatchingLiveModule(): void
	{
		$module = new AppTestModule();
		PradoUnit::setProp($this->_app, '_modules', ['m1' => $module]);
		PradoUnit::setProp($this->_app, '_lazyModules', []);

		$result = $this->_app->getModulesByType(AppTestModule::class);
		$this->assertArrayHasKey('m1', $result);
		$this->assertSame($module, $result['m1']);
	}

	public function testGetModulesByType_excludesNonMatchingModule(): void
	{
		$module = new AppTestModule();
		PradoUnit::setProp($this->_app, '_modules', ['m1' => $module]);
		PradoUnit::setProp($this->_app, '_lazyModules', []);

		$result = $this->_app->getModulesByType(\Prado\Web\THttpSession::class);
		$this->assertSame([], $result);
	}

	public function testGetModulesByType_strict_exactMatchIncluded(): void
	{
		$module = new AppTestModule();
		PradoUnit::setProp($this->_app, '_modules', ['m1' => $module]);
		PradoUnit::setProp($this->_app, '_lazyModules', []);

		$result = $this->_app->getModulesByType(AppTestModule::class, true);
		$this->assertArrayHasKey('m1', $result);
	}

	public function testGetModulesByType_strict_subclassExcluded(): void
	{
		$module = new AppTestModuleSubclass();
		PradoUnit::setProp($this->_app, '_modules', ['sub' => $module]);
		PradoUnit::setProp($this->_app, '_lazyModules', []);

		// strict=true: subclass must not appear when searching by parent class.
		$result = $this->_app->getModulesByType(AppTestModule::class, true);
		$this->assertSame([], $result);
	}

	public function testGetModulesByType_nonStrict_subclassIncluded(): void
	{
		$module = new AppTestModuleSubclass();
		PradoUnit::setProp($this->_app, '_modules', ['sub' => $module]);
		PradoUnit::setProp($this->_app, '_lazyModules', []);

		$result = $this->_app->getModulesByType(AppTestModule::class, false);
		$this->assertArrayHasKey('sub', $result);
	}

	public function testGetModulesByType_includesNullLazyModuleMatchingType(): void
	{
		// A null slot with a matching lazy-module entry must appear with a null value.
		PradoUnit::setProp($this->_app, '_modules', ['lazy_m' => null]);
		PradoUnit::setProp($this->_app, '_lazyModules', [
			'lazy_m' => [AppTestModule::class, [], null],
		]);

		$result = $this->_app->getModulesByType(AppTestModule::class);
		$this->assertArrayHasKey('lazy_m', $result);
		$this->assertNull($result['lazy_m']);
	}

	public function testGetModulesByType_excludesNullLazyModuleNotMatchingType(): void
	{
		PradoUnit::setProp($this->_app, '_modules', ['lazy_m' => null]);
		PradoUnit::setProp($this->_app, '_lazyModules', [
			'lazy_m' => [AppTestModule::class, [], null],
		]);

		$result = $this->_app->getModulesByType(\Prado\Web\THttpSession::class);
		$this->assertSame([], $result);
	}

	public function testGetModulesByType_nullModuleWithoutLazyEntry_excluded(): void
	{
		// Consumed null slot (no lazy entry) must never appear in results.
		PradoUnit::setProp($this->_app, '_modules', ['consumed' => null]);
		PradoUnit::setProp($this->_app, '_lazyModules', []);

		$result = $this->_app->getModulesByType(AppTestModule::class);
		$this->assertSame([], $result);
	}

	public function testGetModulesByType_mixedLiveAndLazyModules(): void
	{
		$live = new AppTestModule();
		PradoUnit::setProp($this->_app, '_modules', [
			'live'   => $live,
			'lazy_m' => null,
			'other'  => null,
		]);
		PradoUnit::setProp($this->_app, '_lazyModules', [
			'lazy_m' => [AppTestModule::class, [], null],
			'other'  => [\Prado\Web\THttpSession::class, [], null],
		]);

		$result = $this->_app->getModulesByType(AppTestModule::class);
		$this->assertCount(2, $result);
		$this->assertArrayHasKey('live', $result);
		$this->assertArrayHasKey('lazy_m', $result);
		$this->assertArrayNotHasKey('other', $result);
		$this->assertSame($live, $result['live']);
		$this->assertNull($result['lazy_m']);
	}

	// -----------------------------------------------------------------------
	// flushOutput
	// -----------------------------------------------------------------------

	public function testFlushOutput_continueBufferingTrue_delegatesToResponse(): void
	{
		$mockResponse = $this->createMock(\Prado\Web\THttpResponse::class);
		$mockResponse->expects($this->once())->method('flush')->with(true);
		PradoUnit::setProp($this->_app, '_response', $mockResponse);

		$this->_app->flushOutput(true);
	}

	public function testFlushOutput_continueBufferingFalse_delegatesToResponse(): void
	{
		$mockResponse = $this->createMock(\Prado\Web\THttpResponse::class);
		$mockResponse->expects($this->once())->method('flush')->with(false);
		PradoUnit::setProp($this->_app, '_response', $mockResponse);

		$this->_app->flushOutput(false);
	}

	// -----------------------------------------------------------------------
	// runService
	// -----------------------------------------------------------------------

	public function testRunService_callsServiceRun(): void
	{
		$mockService = $this->createMock(\Prado\TService::class);
		$mockService->expects($this->once())->method('run');
		$this->_app->setService($mockService);

		$this->_app->runService();
	}

	public function testRunService_doesNothingWhenNoService(): void
	{
		// Should not throw when service is null.
		PradoUnit::setProp($this->_app, '_service', null);
		$this->_app->runService();  // must not throw
		$this->assertTrue(true);   // reached without exception
	}

	// -----------------------------------------------------------------------
	// onEndRequest
	// -----------------------------------------------------------------------

	public function testOnEndRequest_raisesEvent(): void
	{
		$received = false;
		$handler = function () use (&$received) { $received = true; };
		$this->_app->attachEventHandler('OnEndRequest', $handler);

		// onEndRequest() calls flushOutput() → THttpResponse::flush() → header(),
		// which fails in a test environment because headers are already sent.
		// Inject a mock response so flush() is a no-op.
		$mockResponse = $this->createMock(\Prado\Web\THttpResponse::class);
		PradoUnit::setProp($this->_app, '_response', $mockResponse);

		$this->_app->onEndRequest();

		$this->_app->detachEventHandler('OnEndRequest', $handler);
		$this->assertTrue($received, 'OnEndRequest event was not raised');
	}

	public function testOnEndRequest_savesGlobalsWhenStateChanged(): void
	{
		PradoUnit::setProp($this->_app, '_stateChanged', true);
		PradoUnit::setProp($this->_app, '_globals', ['end' => 'value']);

		$mockPersister = $this->createMock(\Prado\IStatePersister::class);
		$mockPersister->expects($this->once())->method('save')->with(['end' => 'value']);
		$this->_app->setApplicationStatePersister($mockPersister);

		$mockResponse = $this->createMock(\Prado\Web\THttpResponse::class);
		PradoUnit::setProp($this->_app, '_response', $mockResponse);

		$this->_app->onEndRequest();
	}

	public function testOnEndRequest_orderIsFlushThenEventThenSaveGlobals(): void
	{
		$callOrder = [];

		$mockResponse = $this->createMock(\Prado\Web\THttpResponse::class);
		$mockResponse->method('flush')->willReturnCallback(function () use (&$callOrder) {
			$callOrder[] = 'flush';
		});
		PradoUnit::setProp($this->_app, '_response', $mockResponse);

		$handler = function () use (&$callOrder) { $callOrder[] = 'event'; };
		$this->_app->attachEventHandler('OnEndRequest', $handler);

		PradoUnit::setProp($this->_app, '_stateChanged', true);
		PradoUnit::setProp($this->_app, '_globals', ['k' => 'v']);
		$mockPersister = $this->createMock(\Prado\IStatePersister::class);
		$mockPersister->method('save')->willReturnCallback(function () use (&$callOrder) {
			$callOrder[] = 'save';
		});
		$this->_app->setApplicationStatePersister($mockPersister);

		$this->_app->onEndRequest();

		$this->_app->detachEventHandler('OnEndRequest', $handler);
		$this->assertSame(['flush', 'event', 'save'], $callOrder);
	}

	// -----------------------------------------------------------------------
	// onError
	// -----------------------------------------------------------------------

	public function testOnError_raisesEvent(): void
	{
		$exception = new \Exception('test error');
		$receivedParam = null;
		$handler = function ($sender, $param) use (&$receivedParam) { $receivedParam = $param; };
		$this->_app->attachEventHandler('OnError', $handler);

		$mockHandler = $this->createMock(\Prado\Exceptions\TErrorHandler::class);
		$mockHandler->expects($this->once())->method('handleError');
		PradoUnit::setProp($this->_app, '_errorHandler', $mockHandler);

		$this->_app->onError($exception);

		$this->_app->detachEventHandler('OnError', $handler);
		$this->assertSame($exception, $receivedParam);
	}

	public function testOnError_delegatesToErrorHandler(): void
	{
		$exception = new \Exception('handler test');

		$mockHandler = $this->createMock(\Prado\Exceptions\TErrorHandler::class);
		$mockHandler->expects($this->once())
			->method('handleError')
			->with($this->_app, $exception);
		PradoUnit::setProp($this->_app, '_errorHandler', $mockHandler);

		$this->_app->onError($exception);
	}

	// -----------------------------------------------------------------------
	// New constants (4.3.3)
	// -----------------------------------------------------------------------

	public function testConstant_defaultApplicationMode(): void
	{
		$this->assertSame(TApplicationMode::Debug, TApplication::DEFAULT_APPLICATION_MODE);
	}

	public function testConstant_defaultPageServiceClass(): void
	{
		$this->assertSame(\Prado\Web\Services\TPageService::class, TApplication::DEFAULT_PAGE_SERVICE_CLASS);
	}

	// -----------------------------------------------------------------------
	// Subclass constant overrides
	// -----------------------------------------------------------------------

	public function testSubclass_defaultApplicationMode_overridable(): void
	{
		// The subclass constant is independently defined.
		$this->assertSame(TApplicationMode::Normal, AppCustomMode::DEFAULT_APPLICATION_MODE);
		// The base class constant is unchanged.
		$this->assertSame(TApplicationMode::Debug, TApplication::DEFAULT_APPLICATION_MODE);
	}

	public function testSubclass_defaultPageServiceClass_overridable(): void
	{
		$this->assertSame('Prado\Web\Services\TJsonService', AppCustomPageService::DEFAULT_PAGE_SERVICE_CLASS);
		$this->assertSame(\Prado\Web\Services\TPageService::class, TApplication::DEFAULT_PAGE_SERVICE_CLASS);
	}

	// -----------------------------------------------------------------------
	// getSteps()
	// -----------------------------------------------------------------------

	/** Returns a TApplicationTestAccessor instance via newInstanceWithoutConstructor(). */
	private function newAccessor(): TApplicationTestAccessor
	{
		$ref = new \ReflectionClass(TApplicationTestAccessor::class);
		return $ref->newInstanceWithoutConstructor();
	}

	public function testGetSteps_returns13Elements(): void
	{
		$steps = $this->newAccessor()->pubGetSteps();
		$this->assertCount(13, $steps);
	}

	public function testGetSteps_correctOrder(): void
	{
		$expected = [
			'onBeginRequest',
			'onLoadState',
			'onLoadStateComplete',
			'onAuthentication',
			'onAuthenticationComplete',
			'onAuthorization',
			'onAuthorizationComplete',
			'onPreRunService',
			'runService',
			'onSaveState',
			'onSaveStateComplete',
			'onPreFlushOutput',
			'flushOutput',
		];
		$this->assertSame($expected, $this->newAccessor()->pubGetSteps());
	}

	public function testGetSteps_returnsSameArrayOnRepeatedCalls(): void
	{
		$acc = $this->newAccessor();
		$this->assertSame($acc->pubGetSteps(), $acc->pubGetSteps());
	}

	public function testGetSteps_subclassCanOverride(): void
	{
		$ref = new \ReflectionClass(AppCustomSteps::class);
		$app = $ref->newInstanceWithoutConstructor();

		$rm = new \ReflectionMethod(AppCustomSteps::class, 'getSteps');
		$rm->setAccessible(true);

		$this->assertSame(AppCustomSteps::CUSTOM_STEPS, $rm->invoke($app));
	}

	public function testGetSteps_baseClassStepsUnchangedBySubclass(): void
	{
		// Verify the static backing array is not mutated by a subclass override.
		$base = $this->newAccessor()->pubGetSteps();
		$this->assertCount(13, $base);
	}

	// -----------------------------------------------------------------------
	// getStep() / setStep()
	// -----------------------------------------------------------------------

	public function testGetStep_initiallyZeroAfterReset(): void
	{
		$acc = $this->newAccessor();
		PradoUnit::setProp($acc, '_step', 0);

		$this->assertSame(0, $acc->pubGetStep());
	}

	public function testSetStep_viaReflection_roundTrip(): void
	{
		$acc = $this->newAccessor();
		$rm  = new \ReflectionMethod(TApplication::class, 'setStep');
		$rm->setAccessible(true);

		$rm->invoke($acc, 7);
		$this->assertSame(7, $acc->pubGetStep());
	}

	public function testSetStep_viaReflection_zero(): void
	{
		$acc = $this->newAccessor();
		$rm  = new \ReflectionMethod(TApplication::class, 'setStep');
		$rm->setAccessible(true);
		$rm->invoke($acc, 3);
		$rm->invoke($acc, 0);

		$this->assertSame(0, $acc->pubGetStep());
	}

	// -----------------------------------------------------------------------
	// buildCacheFilePath()
	// -----------------------------------------------------------------------

	public function testBuildCacheFilePath_appendsCacheFileName(): void
	{
		$acc  = $this->newAccessor();
		$path = '/some/runtime';
		$expected = $path . DIRECTORY_SEPARATOR . TApplication::CONFIGCACHE_FILE;

		$this->assertSame($expected, $acc->pubBuildCacheFilePath($path));
	}

	public function testBuildCacheFilePath_usesDirectorySeparator(): void
	{
		$acc    = $this->newAccessor();
		$result = $acc->pubBuildCacheFilePath('/base');
		$this->assertStringContainsString(DIRECTORY_SEPARATOR, $result);
	}

	public function testBuildCacheFilePath_trailingSlashNotAdded(): void
	{
		$acc = $this->newAccessor();
		// The method should not double-add a separator.
		$result = $acc->pubBuildCacheFilePath('/base');
		$this->assertStringEndsWith(TApplication::CONFIGCACHE_FILE, $result);
	}

	// -----------------------------------------------------------------------
	// getCacheFile() / setCacheFile()
	// -----------------------------------------------------------------------

	public function testGetSetCacheFile_roundTrip(): void
	{
		$acc = $this->newAccessor();
		$acc->pubSetCacheFile('/some/path/config.cache');
		$this->assertSame('/some/path/config.cache', $acc->pubGetCacheFile());
	}

	public function testSetCacheFile_acceptsNull(): void
	{
		$acc = $this->newAccessor();
		$acc->pubSetCacheFile('/any/path');
		$acc->pubSetCacheFile(null);
		$this->assertNull($acc->pubGetCacheFile());
	}

	public function testGetCacheFile_nullByDefault(): void
	{
		// Without a constructor, _cacheFile starts as null (uninitialized).
		$acc = $this->newAccessor();
		$this->assertNull($acc->pubGetCacheFile());
	}

	// -----------------------------------------------------------------------
	// setRuntimePathDirect()
	// -----------------------------------------------------------------------

	public function testSetRuntimePathDirect_setsPathWithoutSideEffects(): void
	{
		$acc = $this->newAccessor();

		// Prime _cacheFile to a sentinel to confirm it is NOT changed.
		$acc->pubSetCacheFile('/original/cache');
		$originalUid  = null; // not set without constructor

		$acc->pubSetRuntimePathDirect('/new/runtime/direct');

		// RuntimePath must reflect the new value.
		$this->assertSame('/new/runtime/direct', $acc->getRuntimePath());
		// CacheFile must NOT have been updated (no side effects).
		$this->assertSame('/original/cache', $acc->pubGetCacheFile());
	}

	public function testSetRuntimePathDirect_acceptsArbitraryString(): void
	{
		$acc = $this->newAccessor();
		$acc->pubSetRuntimePathDirect('/totally/arbitrary');
		$this->assertSame('/totally/arbitrary', $acc->getRuntimePath());
	}

	// -----------------------------------------------------------------------
	// generateAppUniqueId()
	// -----------------------------------------------------------------------

	public function testGenerateAppUniqueId_returnsMd5Format(): void
	{
		$acc = $this->newAccessor();
		$id  = $acc->pubGenerateAppUniqueId('/some/path');
		$this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $id);
	}

	public function testGenerateAppUniqueId_samTokenSameResult(): void
	{
		$acc = $this->newAccessor();
		$this->assertSame(
			$acc->pubGenerateAppUniqueId('/runtime/path'),
			$acc->pubGenerateAppUniqueId('/runtime/path')
		);
	}

	public function testGenerateAppUniqueId_differentTokensDifferentIds(): void
	{
		$acc = $this->newAccessor();
		$this->assertNotSame(
			$acc->pubGenerateAppUniqueId('/path/a'),
			$acc->pubGenerateAppUniqueId('/path/b')
		);
	}

	public function testGenerateAppUniqueId_matchesMd5OfToken(): void
	{
		$acc   = $this->newAccessor();
		$token = '/runtime/abc';
		$this->assertSame(md5($token), $acc->pubGenerateAppUniqueId($token));
	}

	// -----------------------------------------------------------------------
	// setUniqueID() (protected)
	// -----------------------------------------------------------------------

	public function testSetUniqueID_roundTrip(): void
	{
		$acc = $this->newAccessor();
		$acc->pubSetUniqueID('my-custom-unique-id');
		$this->assertSame('my-custom-unique-id', $acc->getUniqueID());
	}

	public function testSetUniqueID_overwritesPreviousValue(): void
	{
		$acc = $this->newAccessor();
		$acc->pubSetUniqueID('first');
		$acc->pubSetUniqueID('second');
		$this->assertSame('second', $acc->getUniqueID());
	}

	// -----------------------------------------------------------------------
	// setRequestCompleted() (protected)
	// -----------------------------------------------------------------------

	public function testSetRequestCompleted_trueValue(): void
	{
		$acc = $this->newAccessor();
		$acc->pubSetRequestCompleted(true);
		$this->assertTrue($acc->getRequestCompleted());
	}

	public function testSetRequestCompleted_falseValue(): void
	{
		$acc = $this->newAccessor();
		$acc->pubSetRequestCompleted(true);
		$acc->pubSetRequestCompleted(false);
		$this->assertFalse($acc->getRequestCompleted());
	}

	// -----------------------------------------------------------------------
	// hasLazyModule() / getLazyModule() / setLazyModule() / getLazyModuleCount()
	// -----------------------------------------------------------------------

	private function newLazyAccessor(): TApplicationTestAccessor
	{
		$acc = $this->newAccessor();
		// Initialise _lazyModules to empty array (uninitialised without constructor).
		PradoUnit::setProp($acc, '_lazyModules', []);
		return $acc;
	}

	public function testHasLazyModule_falseWhenNotRegistered(): void
	{
		$acc = $this->newLazyAccessor();
		$this->assertFalse($acc->pubHasLazyModule('nonexistent'));
	}

	public function testSetLazyModule_registersConfig(): void
	{
		$acc    = $this->newLazyAccessor();
		$config = ['MyClass', ['prop' => 'val'], null];
		$acc->pubSetLazyModule('mymod', $config);
		$this->assertSame($config, $acc->pubGetLazyModule('mymod'));
	}

	public function testHasLazyModule_trueAfterRegistration(): void
	{
		$acc = $this->newLazyAccessor();
		$acc->pubSetLazyModule('mymod', ['Class', [], null]);
		$this->assertTrue($acc->pubHasLazyModule('mymod'));
	}

	public function testGetLazyModule_returnsNullForUnregistered(): void
	{
		$acc = $this->newLazyAccessor();
		$this->assertNull($acc->pubGetLazyModule('ghost'));
	}

	public function testSetLazyModule_nullMarksSlotConsumed(): void
	{
		$acc = $this->newLazyAccessor();
		$acc->pubSetLazyModule('consumed', ['C', [], null]);
		// Nullify to mark as consumed (module has been loaded).
		$acc->pubSetLazyModule('consumed', null);
		$this->assertNull($acc->pubGetLazyModule('consumed'));
	}

	public function testHasLazyModule_falseAfterConsumed(): void
	{
		// isset() on a null array value returns false.
		$acc = $this->newLazyAccessor();
		$acc->pubSetLazyModule('mod', ['C', [], null]);
		$acc->pubSetLazyModule('mod', null);
		$this->assertFalse($acc->pubHasLazyModule('mod'));
	}

	public function testGetLazyModuleCount_zeroForEmpty(): void
	{
		$acc = $this->newLazyAccessor();
		$this->assertSame(0, $acc->pubGetLazyModuleCount());
	}

	public function testGetLazyModuleCount_incrementsOnEachSet(): void
	{
		$acc = $this->newLazyAccessor();
		$acc->pubSetLazyModule('a', ['A', [], null]);
		$acc->pubSetLazyModule('b', ['B', [], null]);
		$this->assertSame(2, $acc->pubGetLazyModuleCount());
	}

	public function testGetLazyModuleCount_includesConsumedSlots(): void
	{
		// Consumed (nulled) slots still contribute to the count — they are used
		// as auto-ID seeds to prevent duplicate anonymous module IDs.
		$acc = $this->newLazyAccessor();
		$acc->pubSetLazyModule('mod', ['C', [], null]);
		$acc->pubSetLazyModule('mod', null);  // consume
		$this->assertSame(1, $acc->pubGetLazyModuleCount());
	}

	public function testSetLazyModule_overwritesExistingConfig(): void
	{
		$acc      = $this->newLazyAccessor();
		$original = ['First', [], null];
		$updated  = ['Second', ['x' => 1], null];
		$acc->pubSetLazyModule('mod', $original);
		$acc->pubSetLazyModule('mod', $updated);
		$this->assertSame($updated, $acc->pubGetLazyModule('mod'));
	}

	// -----------------------------------------------------------------------
	// getConfigurationFileName() — instance property, not static cache
	// -----------------------------------------------------------------------

	public function testGetConfigurationFileName_xmlByDefault(): void
	{
		$acc = $this->newAccessor();
		// Initialise type to xml and reset the cached value.
		$acc->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		PradoUnit::setProp($acc, '_configFileName', null);

		$this->assertSame(TApplication::CONFIG_FILE_XML, $acc->getConfigurationFileName());
	}

	public function testGetConfigurationFileName_phpType(): void
	{
		$acc = $this->newAccessor();
		$acc->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		PradoUnit::setProp($acc, '_configFileName', null);

		$this->assertSame(TApplication::CONFIG_FILE_PHP, $acc->getConfigurationFileName());
	}

	public function testGetConfigurationFileName_cachedOnRepeatCall(): void
	{
		$acc = $this->newAccessor();
		$acc->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		PradoUnit::setProp($acc, '_configFileName', null);

		$first  = $acc->getConfigurationFileName();
		$second = $acc->getConfigurationFileName();
		$this->assertSame($first, $second);
	}

	public function testGetConfigurationFileName_instancePropertyNotStatic(): void
	{
		// Two independent accessor instances must cache independently.
		$acc1 = $this->newAccessor();
		$acc2 = $this->newAccessor();

		$acc1->setConfigurationType(TApplication::CONFIG_TYPE_XML);
		PradoUnit::setProp($acc1, '_configFileName', null);
		$acc2->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		PradoUnit::setProp($acc2, '_configFileName', null);

		// Each instance resolves independently.
		$this->assertSame(TApplication::CONFIG_FILE_XML, $acc1->getConfigurationFileName());
		$this->assertSame(TApplication::CONFIG_FILE_PHP, $acc2->getConfigurationFileName());
	}

	// -----------------------------------------------------------------------
	// Constructor effects
	// -----------------------------------------------------------------------

	public function testConstructor_setsApplicationAlias(): void
	{
		// The constructor calls Prado::setPathOfAlias('Application', $this->getBasePath()).
		$this->assertSame(
			$this->_app->getBasePath(),
			\Prado\Prado::getPathOfAlias('Application')
		);
	}

}

// =============================================================================
// Helper classes for TApplicationConfigurationClassTest
// =============================================================================

/**
 * A TApplicationConfiguration subclass that counts every instantiation and
 * no-ops all file-loading methods so no real filesystem access occurs.
 */
class AppConfigurationSpy extends \Prado\TApplicationConfiguration
{
	public static int $instanceCount = 0;

	public static function resetCount(): void
	{
		self::$instanceCount = 0;
	}

	public function __construct()
	{
		parent::__construct();
		self::$instanceCount++;
	}

	public function loadFromFile($fname): void {}

	public function loadFromXml($dom, $configPath): void {}

	public function loadFromPhp($config, $configPath): void {}
}

/**
 * A minimal concrete TService for testing startService() without real
 * service infrastructure.
 */
class AppConfigTestService extends \Prado\TService
{
	public function run(): void {}
}

/**
 * A THttpRequest stub whose resolveRequest() always returns null so that
 * initApplication() falls through to the page service ID without touching
 * $_SERVER or URL parsing.
 */
class AppStubRequest extends \Prado\Web\THttpRequest
{
	public function init($config): void {}

	public function resolveRequest($serviceIDs): ?string
	{
		return null;
	}
}

/**
 * TApplication subclass that returns AppConfigurationSpy from
 * getApplicationConfigurationClass(). Used as a base for all tests in
 * TApplicationConfigurationClassTest.
 */
class ConfigClassTestApp extends TApplication
{
	protected function getApplicationConfigurationClass(): string
	{
		return AppConfigurationSpy::class;
	}
}

/**
 * Extends ConfigClassTestApp and stubs applyConfiguration() so that
 * startService() can be called without triggering full configuration
 * application side-effects.
 */
class StartServiceConfigClassApp extends ConfigClassTestApp
{
	public function applyConfiguration($config, $withinService = false): void {}
}

/**
 * Extends ConfigClassTestApp and stubs everything that runs after the
 * configuration-file loading block so that initApplication() can be called
 * in isolation, verifying only the config-class instantiation path.
 */
class InitConfigClassTestApp extends ConfigClassTestApp
{
	public function applyConfiguration($config, $withinService = false): void {}

	public function onConfiguration(): void {}

	public function onInitComplete(): void {}

	public function startService($serviceID): void {}

	public function getRequest(): \Prado\Web\THttpRequest
	{
		return new AppStubRequest();
	}
}

// =============================================================================

/**
 * Verifies that TApplication::getApplicationConfigurationClass() is honoured
 * at every instantiation site:
 *
 *   1. initApplication()     — fresh config-file parse (cache absent / stale)
 *   2. startService()        — service sub-configuration element
 *   3. applyConfiguration()  — external include files
 *
 * Each test uses a subclass whose getApplicationConfigurationClass() returns
 * AppConfigurationSpy. After the relevant method is called the spy's instance
 * counter confirms (or denies) that the override was respected.
 *
 * @package System
 */
class TApplicationConfigurationClassTest extends PHPUnit\Framework\TestCase
{
	// -----------------------------------------------------------------------
	// Shared helpers
	// -----------------------------------------------------------------------

	/** Create an instance of $class via newInstanceWithoutConstructor(). */
	private function newApp(string $class): TApplication
	{
		$ref = new \ReflectionClass($class);
		return $ref->newInstanceWithoutConstructor();
	}

	/** Write `$_services` on an app instance via PradoUnit. */
	private function setServices(TApplication $app, array $services): void
	{
		PradoUnit::setProp($app, '_services', $services);
	}

	/** Set a single named private/protected property via PradoUnit. */
	private function setProp(TApplication $app, string $name, mixed $value): void
	{
		PradoUnit::setProp($app, $name, $value);
	}

	// -----------------------------------------------------------------------
	// startService() — config class used when service has a config element
	// -----------------------------------------------------------------------

	public function testStartService_usesConfigClass_whenServiceHasConfigElement(): void
	{
		AppConfigurationSpy::resetCount();

		$app = $this->newApp(StartServiceConfigClassApp::class);
		$this->setProp($app, '_configType', TApplication::CONFIG_TYPE_XML);
		$this->setProp($app, '_basePath', sys_get_temp_dir());
		$this->setProp($app, '_pageServiceID', 'svc');
		$this->setServices($app, [
			'svc' => [AppConfigTestService::class, [], new \Prado\Xml\TXmlElement('service')],
		]);

		$app->startService('svc');

		$this->assertSame(1, AppConfigurationSpy::$instanceCount,
			'startService() must instantiate the class returned by getApplicationConfigurationClass() when the service has a config element');
	}

	public function testStartService_doesNotUseConfigClass_whenServiceConfigElementIsNull(): void
	{
		AppConfigurationSpy::resetCount();

		$app = $this->newApp(StartServiceConfigClassApp::class);
		$this->setProp($app, '_configType', TApplication::CONFIG_TYPE_XML);
		$this->setProp($app, '_basePath', sys_get_temp_dir());
		$this->setProp($app, '_pageServiceID', 'svc');
		$this->setServices($app, [
			'svc' => [AppConfigTestService::class, [], null],  // null configElement
		]);

		$app->startService('svc');

		$this->assertSame(0, AppConfigurationSpy::$instanceCount,
			'startService() must not instantiate the config class when configElement is null');
	}

	// -----------------------------------------------------------------------
	// initApplication() — config class used for fresh parse (no cache)
	// -----------------------------------------------------------------------

	public function testInitApplication_usesConfigClass_forFreshParse(): void
	{
		AppConfigurationSpy::resetCount();

		$configFile = tempnam(sys_get_temp_dir(), 'prado_cfg_') . '.xml';
		file_put_contents(
			$configFile,
			'<?xml version="1.0" encoding="utf-8"?><application></application>'
		);

		try {
			$app = $this->newApp(InitConfigClassTestApp::class);
			$this->setProp($app, '_configType', TApplication::CONFIG_TYPE_XML);
			$this->setProp($app, '_configFile', $configFile);
			$this->setProp($app, '_cacheFile', null);  // always fresh parse
			$this->setProp($app, '_pageServiceID', 'page');
			$this->setServices($app, [
				'page' => [\Prado\Web\Services\TPageService::class, [], null],
			]);

			$app->initApplication();

			$this->assertSame(1, AppConfigurationSpy::$instanceCount,
				'initApplication() must instantiate the class returned by getApplicationConfigurationClass() when parsing the config file fresh');
		} finally {
			@unlink($configFile);
		}
	}

	public function testInitApplication_doesNotUseConfigClass_whenNoConfigFile(): void
	{
		AppConfigurationSpy::resetCount();

		$app = $this->newApp(InitConfigClassTestApp::class);
		$this->setProp($app, '_configFile', null);  // no config file
		$this->setProp($app, '_pageServiceID', 'page');
		$this->setServices($app, [
			'page' => [\Prado\Web\Services\TPageService::class, [], null],
		]);

		$app->initApplication();

		$this->assertSame(0, AppConfigurationSpy::$instanceCount,
			'initApplication() must not instantiate the config class when there is no config file');
	}

	// -----------------------------------------------------------------------
	// applyConfiguration() — config class used for external includes
	// -----------------------------------------------------------------------

	public function testApplyConfiguration_usesConfigClass_forExternalIncludes(): void
	{
		AppConfigurationSpy::resetCount();

		// Create a temp directory with a minimal XML config file.
		$tmpDir  = sys_get_temp_dir();
		$tmpFile = $tmpDir . DIRECTORY_SEPARATOR . 'prado_ext_include_test.xml';
		file_put_contents(
			$tmpFile,
			'<?xml version="1.0" encoding="utf-8"?><configuration/>'
		);

		// Save and patch Prado's static alias table so the external path resolves.
		$aliasRp = new \ReflectionProperty(\Prado\Prado::class, '_aliases');
		$aliasRp->setAccessible(true);
		$savedAliases = $aliasRp->getValue();
		$patchedAliases = $savedAliases;
		$patchedAliases['PradoExtTest'] = $tmpDir;
		$aliasRp->setValue(null, $patchedAliases);

		try {
			$app = $this->newApp(ConfigClassTestApp::class);
			$this->setProp($app, '_configType', TApplication::CONFIG_TYPE_XML);
			$this->setProp($app, '_configFileExt', TApplication::CONFIG_FILE_EXT_XML);
			$this->setProp($app, '_basePath', $tmpDir);
			$this->setProp($app, '_pageServiceID', 'page');
			$this->setServices($app, []);
			$this->setProp($app, '_parameters', new \Prado\Collections\TMap());
			$this->setProp($app, '_modules', []);
			$this->setProp($app, '_lazyModules', []);

			// A PHPUnit mock that looks like a TApplicationConfiguration instance
			// with a single external include that resolves to the temp file.
			$outerConfig = $this->createMock(\Prado\TApplicationConfiguration::class);
			$outerConfig->method('getIsEmpty')->willReturn(false);
			$outerConfig->method('getAliases')->willReturn([]);
			$outerConfig->method('getUsings')->willReturn([]);
			$outerConfig->method('getProperties')->willReturn([]);
			$outerConfig->method('getServices')->willReturn([]);
			$outerConfig->method('getParameters')->willReturn([]);
			$outerConfig->method('getModules')->willReturn([]);
			// 'PradoExtTest.prado_ext_include_test' resolves to $tmpFile via the alias.
			$outerConfig->method('getExternalConfigurations')
				->willReturn(['PradoExtTest.prado_ext_include_test' => true]);

			$app->applyConfiguration($outerConfig, false);

			$this->assertSame(1, AppConfigurationSpy::$instanceCount,
				'applyConfiguration() must instantiate the class returned by getApplicationConfigurationClass() for external include files');
		} finally {
			$aliasRp->setValue(null, $savedAliases);
			@unlink($tmpFile);
		}
	}

	public function testApplyConfiguration_doesNotUseConfigClass_whenNoExternalIncludes(): void
	{
		AppConfigurationSpy::resetCount();

		$app = $this->newApp(ConfigClassTestApp::class);
		$this->setProp($app, '_configType', TApplication::CONFIG_TYPE_XML);
		$this->setProp($app, '_pageServiceID', 'page');
		$this->setServices($app, []);
		$this->setProp($app, '_parameters', new \Prado\Collections\TMap());
		$this->setProp($app, '_modules', []);
		$this->setProp($app, '_lazyModules', []);

		$outerConfig = $this->createMock(\Prado\TApplicationConfiguration::class);
		$outerConfig->method('getIsEmpty')->willReturn(false);
		$outerConfig->method('getAliases')->willReturn([]);
		$outerConfig->method('getUsings')->willReturn([]);
		$outerConfig->method('getProperties')->willReturn([]);
		$outerConfig->method('getServices')->willReturn([]);
		$outerConfig->method('getParameters')->willReturn([]);
		$outerConfig->method('getModules')->willReturn([]);
		$outerConfig->method('getExternalConfigurations')->willReturn([]);  // none

		$app->applyConfiguration($outerConfig, false);

		$this->assertSame(0, AppConfigurationSpy::$instanceCount,
			'applyConfiguration() must not instantiate the config class when there are no external includes');
	}

	// -----------------------------------------------------------------------
	// applyConfiguration() — withinService flag
	// -----------------------------------------------------------------------

	/** Returns a minimal no-op config mock with the given properties map. */
	private function minimalConfigMock(array $properties = [], array $externalConfigs = []): \Prado\TApplicationConfiguration
	{
		$config = $this->createMock(\Prado\TApplicationConfiguration::class);
		$config->method('getIsEmpty')->willReturn(false);
		$config->method('getAliases')->willReturn([]);
		$config->method('getUsings')->willReturn([]);
		$config->method('getProperties')->willReturn($properties);
		$config->method('getServices')->willReturn([]);
		$config->method('getParameters')->willReturn([]);
		$config->method('getModules')->willReturn([]);
		$config->method('getExternalConfigurations')->willReturn($externalConfigs);
		return $config;
	}

	private function bareApp(): TApplication
	{
		$app = $this->newApp(ConfigClassTestApp::class);
		$this->setProp($app, '_configType', TApplication::CONFIG_TYPE_XML);
		$this->setProp($app, '_configFileExt', TApplication::CONFIG_FILE_EXT_XML);
		$this->setProp($app, '_basePath', sys_get_temp_dir());
		$this->setProp($app, '_pageServiceID', 'page');
		$this->setServices($app, []);
		$this->setProp($app, '_parameters', new \Prado\Collections\TMap());
		$this->setProp($app, '_modules', []);
		$this->setProp($app, '_lazyModules', []);
		return $app;
	}

	public function testApplyConfiguration_withinService_skipsPropertyApplication(): void
	{
		$app = $this->bareApp();
		$this->setProp($app, '_mode', \Prado\TApplicationMode::Debug);

		// Config says Mode=Performance — must be ignored when withinService=true.
		$config = $this->minimalConfigMock(['Mode' => \Prado\TApplicationMode::Performance]);

		$app->applyConfiguration($config, true);

		$this->assertSame(\Prado\TApplicationMode::Debug, $app->getMode());
	}

	public function testApplyConfiguration_notWithinService_appliesProperties(): void
	{
		$app = $this->bareApp();
		$this->setProp($app, '_mode', \Prado\TApplicationMode::Debug);

		// Config says Mode=Performance — must be applied when withinService=false.
		$config = $this->minimalConfigMock(['Mode' => \Prado\TApplicationMode::Performance]);

		$app->applyConfiguration($config, false);

		$this->assertSame(\Prado\TApplicationMode::Performance, $app->getMode());
	}

	// -----------------------------------------------------------------------
	// applyConfiguration() — external include condition/path edge cases
	// -----------------------------------------------------------------------

	public function testApplyConfiguration_skipsExternalIncludeWhenConditionFalse(): void
	{
		AppConfigurationSpy::resetCount();

		$app = $this->bareApp();
		// condition is boolean false — include must be skipped without evaluating the path.
		$config = $this->minimalConfigMock([], ['SomeAlias.config' => false]);

		$app->applyConfiguration($config, false);

		$this->assertSame(0, AppConfigurationSpy::$instanceCount,
			'applyConfiguration() must not load an external include when condition is false');
	}

	public function testApplyConfiguration_throwsWhenExternalIncludeFileInvalid(): void
	{
		$app = $this->bareApp();
		// An alias that cannot be resolved → Prado::getPathOfNamespace returns null.
		$config = $this->minimalConfigMock([], ['NonExistentAlias999.config' => true]);

		$this->expectException(\Prado\Exceptions\TConfigurationException::class);
		$app->applyConfiguration($config, false);
	}
}
