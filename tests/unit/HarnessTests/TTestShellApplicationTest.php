<?php

/**
 * TTestShellApplicationTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Prado;
use Prado\Shell\TShellApplication;
use Prado\Shell\TShellWriter;
use Prado\IO\TTextWriter;
use Prado\TApplication;

/**
 * Unit tests for {@see TTestShellApplication}.
 *
 * Verifies the test-helper class itself: singleton registration and restoration,
 * Application alias save/restore, idempotency of restoreApplication(), the destructor
 * safety net, the always-temp-dir resolvePaths() override, and every pub* accessor
 * wrapper.
 *
 * @package System.Shell
 */
class TTestShellApplicationTest extends PHPUnit\Framework\TestCase
{
	/** @var TTestShellApplication The helper under test, created fresh each test. */
	private TTestShellApplication $_app;

	/** @var TApplication The bootstrap application, saved before setUp() creates $_app. */
	private TApplication $_bootstrapApp;

	/** @var ?string The `Application` alias in force before setUp() creates $_app. */
	private ?string $_bootstrapAlias;

	protected function setUp(): void
	{
		$this->_bootstrapApp   = Prado::getApplication();
		$this->_bootstrapAlias = Prado::getPathOfAlias('Application');

		$this->_app = new TTestShellApplication();
	}

	protected function tearDown(): void
	{
		$this->_app->restoreApplication();
	}

	// -----------------------------------------------------------------------
	// Constructor
	// -----------------------------------------------------------------------

	public function testConstructor_registersSelfAsSingleton(): void
	{
		$this->assertSame($this->_app, Prado::getApplication());
	}

	public function testConstructor_resolvesToTempDir_regardlessOfArgument(): void
	{
		// resolvePaths() ignores $basePath and always uses sys_get_temp_dir().
		$this->assertSame(realpath(sys_get_temp_dir()), realpath($this->_app->getBasePath()));
	}

	public function testConstructor_isInstanceOfTShellApplication(): void
	{
		$this->assertInstanceOf(TShellApplication::class, $this->_app);
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
	// restoreApplication() — Application alias
	// -----------------------------------------------------------------------

	public function testRestoreApplication_restoresBootstrapAlias(): void
	{
		$this->_app->restoreApplication();

		$this->assertSame($this->_bootstrapAlias, Prado::getPathOfAlias('Application'));
	}

	// -----------------------------------------------------------------------
	// restoreApplication() — idempotency
	// -----------------------------------------------------------------------

	public function testRestoreApplication_isIdempotent_noExceptionOnDoubleCall(): void
	{
		$this->_app->restoreApplication();
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
	// Destructor safety net
	// -----------------------------------------------------------------------

	public function testDestructor_restoresPreviousSingleton(): void
	{
		$inner = new TTestShellApplication();
		$this->assertSame($inner, Prado::getApplication());

		// Prado::$_application holds a direct (non-cyclic) reference to $inner.
		// Clearing it first allows unset() to drop the refcount to zero and fire
		// __destruct() immediately, before tearDown() runs. Without this, the
		// destructor fires during tearDown() and incorrectly overwrites the
		// already-restored singleton, contaminating subsequent test classes.
		PradoUnit::setStaticProp(Prado::class, '_application', null);
		unset($inner);
		// __destruct() → restoreApplication() fires immediately here.

		$this->assertSame($this->_app, Prado::getApplication());
	}

	public function testDestructor_restoresPreviousAlias(): void
	{
		$aliasBefore = Prado::getPathOfAlias('Application');

		$inner = new TTestShellApplication();
		PradoUnit::setStaticProp(Prado::class, '_application', null);
		unset($inner);

		$this->assertSame($aliasBefore, Prado::getPathOfAlias('Application'));
	}

	// -----------------------------------------------------------------------
	// pub* wrappers — options
	// -----------------------------------------------------------------------

	public function testPubGetOptions_returnsArray(): void
	{
		$this->assertIsArray($this->_app->pubGetOptions());
	}

	public function testPubGetOptionAliases_returnsArray(): void
	{
		$this->assertIsArray($this->_app->pubGetOptionAliases());
	}

	public function testPubGetOptionsData_returnsArray(): void
	{
		$this->assertIsArray($this->_app->pubGetOptionsData());
	}

	public function testPubGetOptions_reflectsRegisteredOption(): void
	{
		$this->_app->registerOption('myflag', function ($v) {}, 'My flag description');

		$this->assertArrayHasKey('myflag', $this->_app->pubGetOptions());
	}

	public function testPubGetOptionAliases_reflectsRegisteredAlias(): void
	{
		$this->_app->registerOption('myflag', function ($v) {}, 'My flag');
		$this->_app->registerOptionAlias('m', 'myflag');

		$aliases = $this->_app->pubGetOptionAliases();
		$this->assertArrayHasKey('m', $aliases);
		$this->assertSame('myflag', $aliases['m']);
	}

	public function testPubGetOptionsData_reflectsRegisteredOptionData(): void
	{
		$this->_app->registerOption('myflag', function ($v) {}, 'My flag description', '=<value>');

		$data = $this->_app->pubGetOptionsData();
		$this->assertArrayHasKey('myflag', $data);
		$this->assertSame(['My flag description', '=<value>'], $data['myflag']);
	}

	// -----------------------------------------------------------------------
	// pub* wrappers — help-printed guard
	// -----------------------------------------------------------------------

	public function testPubIsHelpPrinted_defaultFalse(): void
	{
		$this->assertFalse($this->_app->pubIsHelpPrinted());
	}

	public function testPubSetHelpPrinted_roundTripsTrue(): void
	{
		$this->_app->pubSetHelpPrinted(true);
		$this->assertTrue($this->_app->pubIsHelpPrinted());
	}

	public function testPubSetHelpPrinted_roundTripsFalse(): void
	{
		$this->_app->pubSetHelpPrinted(true);
		$this->_app->pubSetHelpPrinted(false);
		$this->assertFalse($this->_app->pubIsHelpPrinted());
	}

	// -----------------------------------------------------------------------
	// pub* wrappers — arguments
	// -----------------------------------------------------------------------

	public function testPubGetArguments_defaultEmpty(): void
	{
		$this->assertSame([], $this->_app->pubGetArguments());
	}

	public function testPubSetArguments_storesAndReturnsArray(): void
	{
		$args = ['foo', '--bar', 'baz'];
		$this->_app->pubSetArguments($args);

		$this->assertSame($args, $this->_app->pubGetArguments());
	}

	public function testPubSetArguments_emptyArray_clearsArguments(): void
	{
		$this->_app->pubSetArguments(['a', 'b']);
		$this->_app->pubSetArguments([]);

		$this->assertSame([], $this->_app->pubGetArguments());
	}

	// -----------------------------------------------------------------------
	// pub* wrappers — writer direct
	// -----------------------------------------------------------------------

	public function testPubGetWriterDirect_defaultNull(): void
	{
		$this->assertNull($this->_app->pubGetWriterDirect());
	}

	public function testPubGetWriterDirect_returnsWriterAfterSet(): void
	{
		$writer = new TShellWriter(new TTextWriter());
		$this->_app->setWriter($writer);

		$this->assertSame($writer, $this->_app->pubGetWriterDirect());
	}

	public function testPubGetWriterDirect_returnsNullAfterFlushWithoutBuffering(): void
	{
		$writer = new TShellWriter(new TTextWriter());
		$this->_app->setWriter($writer);

		$this->_app->flushOutput(false);

		$this->assertNull($this->_app->pubGetWriterDirect());
	}
}
