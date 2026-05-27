<?php

/**
 * TTestApplicationTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

require_once __DIR__ . '/../PradoUnitRequires.php';

use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\TApplicationMode;

/**
 * Unit tests for {@see TTestApplication}.
 *
 * Covers: singleton registration and restoration, Application alias save/restore,
 * idempotency of restoreApplication(), destructor safety net, resolvePaths() error
 * path, exit-code capture, and the snapshotApp() / restoreApp() helpers.
 *
 * Every test method creates its own fresh TTestApplication in setUp() and tears it
 * down in tearDown() — each test therefore starts with the global singleton pointing
 * at the bootstrap application and ends with it restored to the same.
 *
 * @package System
 */
class TTestApplicationTest extends PHPUnit\Framework\TestCase
{
	/** @var TTestApplication The test application under test, created fresh each test. */
	private TTestApplication $_app;

	/** @var TApplication The bootstrap application, saved before setUp() creates $_app. */
	private TApplication $_bootstrapApp;

	/** @var ?string The `Application` alias in force before setUp() creates $_app. */
	private ?string $_bootstrapAlias;

	protected function setUp(): void
	{
		// Record what the global singleton and alias look like before we touch anything.
		$this->_bootstrapApp   = Prado::getApplication();
		$this->_bootstrapAlias = Prado::getPathOfAlias('Application');

		$this->_app = new TTestApplication();
	}

	protected function tearDown(): void
	{
		$this->_app->restoreApplication();
	}

	// -----------------------------------------------------------------------
	// Constructor defaults
	// -----------------------------------------------------------------------

	public function testConstructor_defaultsBasePath_toTempDir(): void
	{
		$this->assertSame(realpath(sys_get_temp_dir()), realpath($this->_app->getBasePath()));
	}

	public function testConstructor_customBasePath_usedWhenProvided(): void
	{
		$tmpDir = sys_get_temp_dir();
		$app = new TTestApplication($tmpDir);
		$this->assertSame(realpath($tmpDir), realpath($app->getBasePath()));
		$app->restoreApplication();
	}

	// -----------------------------------------------------------------------
	// Singleton registration
	// -----------------------------------------------------------------------

	public function testConstructor_registersSelfAsSingleton(): void
	{
		$this->assertSame($this->_app, Prado::getApplication());
	}

	public function testConstructor_overwritesApplicationAlias(): void
	{
		// After construction, the alias points at the temp-dir basePath.
		$expected = realpath(sys_get_temp_dir());
		$this->assertSame($expected, Prado::getPathOfAlias('Application'));
	}

	// -----------------------------------------------------------------------
	// restoreApplication() — singleton
	// -----------------------------------------------------------------------

	public function testRestoreApplication_restoresBootstrapSingleton(): void
	{
		$this->_app->restoreApplication();

		$this->assertSame($this->_bootstrapApp, Prado::getApplication());
	}

	public function testRestoreApplication_afterRestore_selfNoLongerSingleton(): void
	{
		$this->_app->restoreApplication();

		$this->assertNotSame($this->_app, Prado::getApplication());
	}

	// -----------------------------------------------------------------------
	// restoreApplication() — full alias table
	// -----------------------------------------------------------------------

	public function testRestoreApplication_restoresBootstrapAlias(): void
	{
		$this->_app->restoreApplication();

		$this->assertSame($this->_bootstrapAlias, Prado::getPathOfAlias('Application'));
	}

	public function testRestoreApplication_restoresFullAliasTable(): void
	{
		// Register an extra alias while this app is active.
		$tmpDir = sys_get_temp_dir();
		Prado::setPathOfAlias('PradoUnitTestExtra', $tmpDir);
		$this->assertNotNull(Prado::getPathOfAlias('PradoUnitTestExtra'));

		// Restoring must remove the extra alias (full table is snapshotted, not just 'Application').
		$this->_app->restoreApplication();

		$this->assertNull(Prado::getPathOfAlias('PradoUnitTestExtra'));
	}

	// -----------------------------------------------------------------------
	// restoreApplication() — idempotency
	// -----------------------------------------------------------------------

	public function testRestoreApplication_isIdempotent_noExceptionOnDoubleCall(): void
	{
		$this->_app->restoreApplication();
		// Second call must be a no-op, not throw.
		$this->_app->restoreApplication();

		$this->assertSame($this->_bootstrapApp, Prado::getApplication());
	}

	public function testRestoreApplication_isIdempotent_singletonUnchangedAfterSecondCall(): void
	{
		$this->_app->restoreApplication();
		$afterFirst = Prado::getApplication();

		$this->_app->restoreApplication();

		$this->assertSame($afterFirst, Prado::getApplication());
	}

	// -----------------------------------------------------------------------
	// Alias saved at registerApplication() time — not at restore time
	// -----------------------------------------------------------------------

	public function testRegisterApplication_savesAlias_beforeParentOverwrites(): void
	{
		// The alias saved in the trait must equal what was in force BEFORE this
		// app's constructor called setPathOfAlias('Application', ...).
		// restoreApplication() should therefore put the bootstrap alias back.
		$this->_app->restoreApplication();

		$this->assertSame($this->_bootstrapAlias, Prado::getPathOfAlias('Application'));
	}

	// -----------------------------------------------------------------------
	// Destructor safety net
	// -----------------------------------------------------------------------

	public function testDestructor_restoresPreviousSingleton(): void
	{
		// Create a second TTestApplication that pushes $_app aside.
		$inner = new TTestApplication();
		$this->assertSame($inner, Prado::getApplication());

		// Prado::$_application holds a direct (non-cyclic) reference to $inner.
		// Clearing it first allows unset() to reduce the refcount to zero and fire
		// __destruct() immediately, before tearDown() runs. Without this, the
		// destructor fires during tearDown() and incorrectly overwrites the already-
		// restored singleton, contaminating subsequent test classes.
		PradoUnit::setStaticProp(Prado::class, '_application', null);
		unset($inner);
		// __destruct() → restoreApplication() fires immediately here.

		$this->assertSame($this->_app, Prado::getApplication());
	}

	public function testDestructor_restoresPreviousAlias(): void
	{
		$aliasBeforeInner = Prado::getPathOfAlias('Application');

		$inner = new TTestApplication();
		PradoUnit::setStaticProp(Prado::class, '_application', null);
		unset($inner);

		$this->assertSame($aliasBeforeInner, Prado::getPathOfAlias('Application'));
	}

	// -----------------------------------------------------------------------
	// resolvePaths() — error path
	// -----------------------------------------------------------------------

	public function testResolvePaths_invalidPath_throwsConfigurationException(): void
	{
		$this->expectException(TConfigurationException::class);
		// The partially-constructed object's __destruct() will restore the singleton.
		new TTestApplication('/this/path/does/not/exist/xyz');
	}

	// -----------------------------------------------------------------------
	// capturedExitCode
	// -----------------------------------------------------------------------

	public function testCapturedExitCode_startsNull(): void
	{
		$this->assertNull($this->_app->capturedExitCode);
	}

	public function testExitOverride_storesCapturedExitCode(): void
	{
		$ref = new \ReflectionMethod(TTestApplication::class, 'exit');
		$ref->invoke($this->_app, 42);

		$this->assertSame(42, $this->_app->capturedExitCode);
	}

	public function testExitOverride_doesNotTerminateProcess(): void
	{
		// Verify that calling exit() does not throw or halt — just stores the code.
		$ref = new \ReflectionMethod(TTestApplication::class, 'exit');
		$ref->invoke($this->_app, 0);

		// If we reach this assertion, the process is still running.
		$this->assertSame(0, $this->_app->capturedExitCode);
	}

	// -----------------------------------------------------------------------
	// snapshotApp() / restoreApp()
	// -----------------------------------------------------------------------

	public function testSnapshotApp_returnsNonEmptyArray(): void
	{
		$snapshot = TTestApplication::snapshotApp($this->_app);

		$this->assertIsArray($snapshot);
		$this->assertNotEmpty($snapshot);
	}

	public function testSnapshotApp_capturesKnownProperty(): void
	{
		$snapshot = TTestApplication::snapshotApp($this->_app);

		// _basePath is set during construction and must appear in the snapshot.
		$this->assertArrayHasKey('_basePath', $snapshot);
		$this->assertSame($this->_app->getBasePath(), $snapshot['_basePath']);
	}

	public function testSnapshotApp_withPropNames_capturesOnlyRequested(): void
	{
		$snapshot = TTestApplication::snapshotApp($this->_app, ['_basePath']);

		$this->assertArrayHasKey('_basePath', $snapshot);
		$this->assertCount(1, $snapshot);
	}

	public function testRestoreApp_writesPropertyBack(): void
	{
		// Snapshot before the mutation.
		$snapshot = TTestApplication::snapshotApp($this->_app, ['_mode']);
		$original = $snapshot['_mode'];

		// Mutate the application mode.
		$this->_app->setMode(TApplicationMode::Performance);
		$this->assertNotSame($original, $this->_app->getMode());

		// Restore and verify.
		TTestApplication::restoreApp($snapshot, $this->_app);
		$this->assertSame($original, $this->_app->getMode());
	}

	public function testRestoreApp_partialSnapshot_onlyWritesPresent(): void
	{
		// Take a partial snapshot of just _mode.
		$snapshot = TTestApplication::snapshotApp($this->_app, ['_mode']);

		$originalBasePath = $this->_app->getBasePath();

		// Mutate both properties.
		$this->_app->setMode(TApplicationMode::Performance);
		PradoUnit::setProp($this->_app, '_basePath', '/some/fake/path');

		// Restore only _mode.
		TTestApplication::restoreApp($snapshot, $this->_app);

		// _mode is restored; _basePath mutation was NOT reverted (not in snapshot).
		$this->assertSame($snapshot['_mode'], $this->_app->getMode());
		$this->assertNotSame($originalBasePath, $this->_app->getBasePath());

		// Clean up the basePath mutation to leave the object in a sane state.
		PradoUnit::setProp($this->_app, '_basePath', $originalBasePath);
	}

	public function testSnapshotApp_defaultsToCurrentSingleton(): void
	{
		// Called without an explicit $app argument — must use Prado::getApplication().
		$snapshot = TTestApplication::snapshotApp();

		$this->assertSame($this->_app->getBasePath(), $snapshot['_basePath']);
	}

	public function testRestoreApp_defaultsToCurrentSingleton(): void
	{
		$snapshot = TTestApplication::snapshotApp($this->_app, ['_mode']);
		$this->_app->setMode(TApplicationMode::Performance);

		// No explicit $app — must default to current singleton.
		TTestApplication::restoreApp($snapshot);

		$this->assertSame($snapshot['_mode'], Prado::getApplication()->getMode());
	}
}
