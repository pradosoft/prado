<?php

/**
 * TTestApplicationRestorationTraitTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Prado;
use Prado\TApplication;
use Prado\TComponent;

/**
 * Tests for {@see TTestApplicationRestorationTrait}.
 *
 * Verifies the trait's two responsibilities directly, on TTestApplication
 * (which uses the trait):
 *
 *  1. {@see registerApplication()} snapshots the full static state of
 *     {@see Prado} and {@see TComponent} BEFORE swapping the singleton, so
 *     the snapshot references the previous app.
 *  2. {@see restoreApplication()} writes both snapshots back, restoring the
 *     singleton, the entire alias table, and any class-behavior / global-event
 *     mutations made while the test app was active. It is idempotent and the
 *     destructor calls it as a safety net.
 *
 * @package System.Harness.Traits
 */
class TTestApplicationRestorationTraitTest extends PHPUnit\Framework\TestCase
{
	/** @var TApplication The bootstrap singleton captured before each test. */
	private TApplication $_bootstrapApp;

	/** @var ?string The Application alias in force before each test. */
	private ?string $_bootstrapAlias;

	protected function setUp(): void
	{
		$this->_bootstrapApp   = Prado::getApplication();
		$this->_bootstrapAlias = Prado::getPathOfAlias('Application');
	}

	// -----------------------------------------------------------------------
	// Snapshot is taken BEFORE Prado::setApplication($this)
	// -----------------------------------------------------------------------

	public function testRegisterApplication_snapshotCapturesPreviousSingleton(): void
	{
		$app = new TTestApplication();
		try {
			// The snapshot must reference the bootstrap singleton — i.e. the value
			// of Prado::$_application before the trait registered $this.
			$snap = PradoUnit::getProp($app, '_pradoSnapshot');
			$this->assertIsArray($snap);
			$this->assertArrayHasKey('_application', $snap);
			$this->assertSame($this->_bootstrapApp, $snap['_application']);
		} finally {
			$app->restoreApplication();
		}
	}

	public function testRegisterApplication_snapshotCapturesPreviousAliases(): void
	{
		$app = new TTestApplication();
		try {
			$snap = PradoUnit::getProp($app, '_pradoSnapshot');
			$this->assertArrayHasKey('_aliases', $snap);
			$this->assertSame($this->_bootstrapAlias, $snap['_aliases']['Application'] ?? null);
		} finally {
			$app->restoreApplication();
		}
	}

	public function testRegisterApplication_swapsSingletonToSelf(): void
	{
		$app = new TTestApplication();
		try {
			$this->assertSame($app, Prado::getApplication());
		} finally {
			$app->restoreApplication();
		}
	}

	public function testRegisterApplication_overwritesApplicationAliasToOwnBasePath(): void
	{
		$app = new TTestApplication();
		try {
			$this->assertSame(realpath($app->getBasePath()), Prado::getPathOfAlias('Application'));
		} finally {
			$app->restoreApplication();
		}
	}

	// -----------------------------------------------------------------------
	// restoreApplication() — singleton + alias table
	// -----------------------------------------------------------------------

	public function testRestoreApplication_restoresBootstrapSingleton(): void
	{
		$app = new TTestApplication();
		$app->restoreApplication();
		$this->assertSame($this->_bootstrapApp, Prado::getApplication());
	}

	public function testRestoreApplication_restoresPreviousApplicationAlias(): void
	{
		$app = new TTestApplication();
		$app->restoreApplication();
		$this->assertSame($this->_bootstrapAlias, Prado::getPathOfAlias('Application'));
	}

	public function testRestoreApplication_removesAliasesAddedDuringTest(): void
	{
		$app = new TTestApplication();
		// Add an alias while $app is the active singleton.
		Prado::setPathOfAlias('PradoUnitTraitTest', sys_get_temp_dir());
		$this->assertNotNull(Prado::getPathOfAlias('PradoUnitTraitTest'));

		// Restoring must wipe the alias — the full table was snapshotted.
		$app->restoreApplication();
		$this->assertNull(Prado::getPathOfAlias('PradoUnitTraitTest'));
	}

	// -----------------------------------------------------------------------
	// restoreApplication() — class-behavior and global-event registries
	// -----------------------------------------------------------------------

	public function testRestoreApplication_revertsClassBehaviorMutations(): void
	{
		$app = new TTestApplication();
		$umBefore = PradoUnit::getStaticProp(TComponent::class, '_um');

		// Attach a class behavior to a real leaf class (TComponent itself is
		// forbidden by the recursion guard at TComponent.php:1782).
		TComponent::attachClassBehavior(
			'pradoUnitTraitTestBehavior',
			\Prado\Util\TBehavior::class,
			\Prado\TModule::class
		);

		$app->restoreApplication();

		// _um is restored to its pre-test value, byte for byte.
		$this->assertSame($umBefore, PradoUnit::getStaticProp(TComponent::class, '_um'));
	}

	public function testRestoreApplication_revertsGlobalEventMutations(): void
	{
		$app = new TTestApplication();
		$ueBefore = PradoUnit::getStaticProp(TComponent::class, '_ue');

		// Mutate the global event-handler registry directly.
		$ue = PradoUnit::getStaticProp(TComponent::class, '_ue');
		$ue['fxPradoUnitTraitTest'] = ['someHandler'];
		PradoUnit::setStaticProp(TComponent::class, '_ue', $ue);

		$app->restoreApplication();

		$this->assertSame($ueBefore, PradoUnit::getStaticProp(TComponent::class, '_ue'));
	}

	// -----------------------------------------------------------------------
	// Idempotency — second call is a no-op
	// -----------------------------------------------------------------------

	public function testRestoreApplication_isIdempotent(): void
	{
		$app = new TTestApplication();
		$app->restoreApplication();
		$singletonAfterFirst = Prado::getApplication();

		// Second call must not throw and must not change the singleton.
		$app->restoreApplication();
		$this->assertSame($singletonAfterFirst, Prado::getApplication());
	}

	public function testRestoreApplication_clearsSnapshotsAfterFirstCall(): void
	{
		$app = new TTestApplication();
		$app->restoreApplication();
		$this->assertNull(PradoUnit::getProp($app, '_pradoSnapshot'));
		$this->assertNull(PradoUnit::getProp($app, '_componentSnapshot'));
	}

	// -----------------------------------------------------------------------
	// Destructor safety net
	// -----------------------------------------------------------------------

	public function testDestructor_callsRestoreApplication(): void
	{
		$inner = new TTestApplication();
		$this->assertSame($inner, Prado::getApplication());

		// Drop the Prado singleton reference so refcount can fall to zero and the
		// destructor fires immediately when $inner goes out of scope.
		PradoUnit::setStaticProp(Prado::class, '_application', null);
		unset($inner);

		$this->assertSame($this->_bootstrapApp, Prado::getApplication());
	}

	public function testDestructor_isSafeAfterExplicitRestore(): void
	{
		$inner = new TTestApplication();
		$inner->restoreApplication();
		// Second restore via destructor must not throw.
		unset($inner);
		$this->assertSame($this->_bootstrapApp, Prado::getApplication());
	}
}
