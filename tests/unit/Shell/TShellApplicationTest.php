<?php

/**
 * TShellApplicationTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Caching\ICache;
use Prado\Data\ActiveRecord\TActiveRecordConfig;
use Prado\Prado;
use Prado\Shell\Actions\TActiveRecordAction;
use Prado\Shell\Actions\TFlushCachesAction;
use Prado\Shell\Actions\THelpAction;
use Prado\Shell\Actions\TPhpShellAction;
use Prado\Shell\Actions\TWebServerAction;
use Prado\Shell\TShellApplication;
use Prado\Shell\TShellWriter;
use Prado\TApplicationMode;
use Prado\TModule;

/**
 * A minimal ICache+TModule stub used to verify that {@see TShellApplication::installShellActions()}
 * registers {@see TFlushCachesAction} only when at least one {@see ICache} module is present.
 */
class ShellTestCacheModule extends TModule implements ICache
{
	public function init($config) {}
	public function get($id) { return false; }
	public function set($id, $value, $expire = 0, $dependency = null) { return true; }
	public function add($id, $value, $expire = 0, $dependency = null) { return true; }
	public function delete($id) { return true; }
	public function flush() {}
}

/**
 * A minimal TActiveRecordConfig stub used to verify that
 * {@see TShellApplication::installShellActions()} registers {@see TActiveRecordAction}
 * only when at least one {@see TActiveRecordConfig} module is present.
 */
class ShellTestActiveRecordConfig extends TActiveRecordConfig
{
	public function init($config) {}
}

/**
 * A minimal {@see TShellAction} stub used in processArguments and processActionArguments tests.
 * Declares a `verbose` option (aliased to `-v`) and one `run` method.
 */
class ShellTestAction extends \Prado\Shell\TShellAction
{
	protected $action = 'test';
	protected $methods = ['run'];
	protected $parameters = [null];
	protected $optional = [null];
	protected $description = ['Test action', 'Run the test'];
	protected $defaultMethod = 0;

	/** Property set by --verbose / -v option during processActionArguments. */
	public $verbose = '';

	public function options($actionID): array
	{
		return ['verbose'];
	}

	public function optionAliases(): array
	{
		return ['v' => 'verbose'];
	}

	public function actionRun($args): bool
	{
		return true;
	}
}

/**
 * A minimal {@see TShellAction} stub that declares no options.
 * Used to exercise the early-return path in processActionArguments().
 */
class ShellTestNoOptionAction extends \Prado\Shell\TShellAction
{
	protected $action = 'noop';
	protected $methods = ['run'];
	protected $parameters = [null];
	protected $optional = [null];
	protected $description = ['No-option action', 'No-op run'];
	protected $defaultMethod = 0;

	public function options($actionID): array
	{
		return [];
	}

	public function actionRun($args): bool
	{
		return true;
	}
}

/**
 * Tests for {@see TShellApplication}.
 *
 * Covers: action registration helpers, conditional installShellActions() logic
 * (TFlushCachesAction and TWebServerAction gates), option/alias registration,
 * quiet-mode setter, and the help-printed guard.
 *
 * @package System.Shell
 */
class TShellApplicationTest extends PHPUnit\Framework\TestCase
{
	private TTestShellApplication $_app;

	protected function setUp(): void
	{
		$this->_app = new TTestShellApplication();
	}

	protected function tearDown(): void
	{
		$this->_app->restoreApplication();
	}

	// -----------------------------------------------------------------------
	// Action registration helpers
	// -----------------------------------------------------------------------

	public function testAddAndHasShellActionClass()
	{
		$this->assertFalse($this->_app->hasShellActionClass(THelpAction::class));

		$this->_app->addShellActionClass(THelpAction::class);

		$this->assertTrue($this->_app->hasShellActionClass(THelpAction::class));
	}

	public function testGetShellActionsReturnsMap()
	{
		$this->_app->addShellActionClass(THelpAction::class);
		$this->_app->addShellActionClass(TPhpShellAction::class);

		$actions = $this->_app->getShellActions();

		$this->assertArrayHasKey(THelpAction::class, $actions);
		$this->assertArrayHasKey(TPhpShellAction::class, $actions);
		$this->assertInstanceOf(THelpAction::class, $actions[THelpAction::class]);
		$this->assertInstanceOf(TPhpShellAction::class, $actions[TPhpShellAction::class]);
	}

	public function testAddShellActionClassWithArray()
	{
		$this->_app->addShellActionClass(['class' => THelpAction::class]);

		$this->assertTrue($this->_app->hasShellActionClass(THelpAction::class));
	}

	// -----------------------------------------------------------------------
	// installShellActions — always-present actions
	// -----------------------------------------------------------------------

	public function testInstallShellActionsRegistersAlwaysPresentActions()
	{
		$this->_app->setMode(TApplicationMode::Normal);

		$this->_app->installShellActions();

		$this->assertTrue($this->_app->hasShellActionClass(THelpAction::class));
		$this->assertTrue($this->_app->hasShellActionClass(TPhpShellAction::class));
	}

	// -----------------------------------------------------------------------
	// hasCacheModules / TFlushCachesAction conditional gate
	// -----------------------------------------------------------------------

	public function testHasCacheModulesReturnsFalseWithoutModule()
	{
		$this->assertFalse($this->_app->hasCacheModules());
	}

	public function testHasCacheModulesReturnsTrueWithModule()
	{
		$this->_app->setModule('testCache', new ShellTestCacheModule());

		$this->assertTrue($this->_app->hasCacheModules());
	}

	public function testInstallShellActionsOmitsFlushCachesWithoutCacheModule()
	{
		$this->_app->setMode(TApplicationMode::Normal);

		$this->_app->installShellActions();

		$this->assertFalse(
			$this->_app->hasShellActionClass(TFlushCachesAction::class),
			'TFlushCachesAction must not be registered when no ICache module exists.'
		);
	}

	public function testInstallShellActionsAddsFlushCachesWithCacheModule()
	{
		$this->_app->setMode(TApplicationMode::Normal);
		$this->_app->setModule('testCache', new ShellTestCacheModule());

		$this->_app->installShellActions();

		$this->assertTrue(
			$this->_app->hasShellActionClass(TFlushCachesAction::class),
			'TFlushCachesAction must be registered when at least one ICache module is present.'
		);
	}

	// -----------------------------------------------------------------------
	// hasActiveRecordConfig / TActiveRecordAction conditional gate
	// -----------------------------------------------------------------------

	public function testHasActiveRecordConfigReturnsFalseWithoutModule()
	{
		$this->assertFalse($this->_app->hasActiveRecordConfig());
	}

	public function testHasActiveRecordConfigReturnsTrueWithModule()
	{
		$this->_app->setModule('testAR', new ShellTestActiveRecordConfig());

		$this->assertTrue($this->_app->hasActiveRecordConfig());
	}

	public function testInstallShellActionsOmitsActiveRecordWithoutConfig()
	{
		$this->_app->setMode(TApplicationMode::Normal);

		$this->_app->installShellActions();

		$this->assertFalse(
			$this->_app->hasShellActionClass(TActiveRecordAction::class),
			'TActiveRecordAction must not be registered when no TActiveRecordConfig module exists.'
		);
	}

	public function testInstallShellActionsAddsActiveRecordWithConfig()
	{
		$this->_app->setMode(TApplicationMode::Normal);
		$this->_app->setModule('testAR', new ShellTestActiveRecordConfig());

		$this->_app->installShellActions();

		$this->assertTrue(
			$this->_app->hasShellActionClass(TActiveRecordAction::class),
			'TActiveRecordAction must be registered when at least one TActiveRecordConfig module is present.'
		);
	}

	// -----------------------------------------------------------------------
	// hasDevWebServer
	// -----------------------------------------------------------------------

	public function testHasDevWebServer_returnsFalse_inNormalModeWithoutParam(): void
	{
		$this->_app->setMode(TApplicationMode::Normal);

		$this->assertFalse($this->_app->hasDevWebServer());
	}

	public function testHasDevWebServer_returnsTrue_inDebugMode(): void
	{
		$this->_app->setMode(TApplicationMode::Debug);

		$this->assertTrue($this->_app->hasDevWebServer());
	}

	public function testHasDevWebServer_returnsTrue_whenParamSet(): void
	{
		$this->_app->setMode(TApplicationMode::Normal);
		$this->_app->getParameters()->add(TWebServerAction::DEV_WEBSERVER_PARAM, '1');

		$this->assertTrue($this->_app->hasDevWebServer());
	}

	// -----------------------------------------------------------------------
	// installShellActions — TWebServerAction conditional gate
	// -----------------------------------------------------------------------

	public function testInstallShellActionsAddsWebServerInDebugMode(): void
	{
		$this->_app->setMode(TApplicationMode::Debug);

		$this->_app->installShellActions();

		$this->assertTrue(
			$this->_app->hasShellActionClass(TWebServerAction::class),
			'TWebServerAction must be registered in Debug mode.'
		);
	}

	public function testInstallShellActionsOmitsWebServerInNormalMode(): void
	{
		$this->_app->setMode(TApplicationMode::Normal);

		$this->_app->installShellActions();

		$this->assertFalse(
			$this->_app->hasShellActionClass(TWebServerAction::class),
			'TWebServerAction must not be registered in Normal mode without the dev param.'
		);
	}

	public function testInstallShellActionsAddsWebServerWhenParamSet(): void
	{
		$this->_app->setMode(TApplicationMode::Normal);
		$this->_app->getParameters()->add(TWebServerAction::DEV_WEBSERVER_PARAM, '1');

		$this->_app->installShellActions();

		$this->assertTrue(
			$this->_app->hasShellActionClass(TWebServerAction::class),
			'TWebServerAction must be registered when DEV_WEBSERVER_PARAM is truthy.'
		);
	}

	// -----------------------------------------------------------------------
	// Option / alias registration
	// -----------------------------------------------------------------------

	public function testRegisterOption()
	{
		$called = null;
		$this->_app->registerOption('verbose', function ($v) use (&$called) {
			$called = $v;
		}, 'Enable verbose output', '=<level>');

		$options = $this->_app->pubGetOptions();
		$optionsData = $this->_app->pubGetOptionsData();

		$this->assertArrayHasKey('verbose', $options);
		$this->assertIsCallable($options['verbose']);
		$this->assertSame(['Enable verbose output', '=<level>'], $optionsData['verbose']);

		// Verify the callback fires correctly.
		call_user_func($options['verbose'], '2');
		$this->assertSame('2', $called);
	}

	public function testRegisterOptionAlias()
	{
		$this->_app->registerOption('verbose', function ($v) {}, 'Enable verbose output');
		$this->_app->registerOptionAlias('v', 'verbose');

		$aliases = $this->_app->pubGetOptionAliases();

		$this->assertArrayHasKey('v', $aliases);
		$this->assertSame('verbose', $aliases['v']);
	}

	// -----------------------------------------------------------------------
	// Quiet mode
	// -----------------------------------------------------------------------

	public function testQuietModeDefaultIsZero()
	{
		$this->assertSame(0, $this->_app->getQuietMode());
	}

	public function testSetQuietModeInteger()
	{
		$this->_app->setQuietMode(2);
		$this->assertSame(2, $this->_app->getQuietMode());
	}

	public function testSetQuietModeEmptyStringDefaultsToOne()
	{
		$this->_app->setQuietMode('');
		$this->assertSame(1, $this->_app->getQuietMode());
	}

	public function testSetQuietModeClampsToRange()
	{
		$this->_app->setQuietMode(-5);
		$this->assertSame(0, $this->_app->getQuietMode());

		$this->_app->setQuietMode(99);
		$this->assertSame(3, $this->_app->getQuietMode());
	}

	// -----------------------------------------------------------------------
	// Help-printed guard
	// -----------------------------------------------------------------------

	public function testHelpPrintedDefaultIsFalse()
	{
		$this->assertFalse($this->_app->pubIsHelpPrinted());
	}

	public function testSetHelpPrinted()
	{
		$this->_app->pubSetHelpPrinted(true);
		$this->assertTrue($this->_app->pubIsHelpPrinted());

		$this->_app->pubSetHelpPrinted(false);
		$this->assertFalse($this->_app->pubIsHelpPrinted());
	}

	// -----------------------------------------------------------------------
	// Shell writer
	// -----------------------------------------------------------------------

	public function testSetAndGetWriter()
	{
		$writer = new TShellWriter(new \Prado\IO\TTextWriter());
		$this->_app->setWriter($writer);
		$this->assertSame($writer, $this->_app->getWriter());
	}

	// -----------------------------------------------------------------------
	// flushOutput
	// -----------------------------------------------------------------------

	public function testFlushOutput_continueBufferingTrue_retainsWriter()
	{
		$writer = new TShellWriter(new \Prado\IO\TTextWriter());
		$this->_app->setWriter($writer);

		$this->_app->flushOutput(true);

		$this->assertNotNull($this->_app->pubGetWriterDirect());
		$this->assertSame($writer, $this->_app->pubGetWriterDirect());
	}

	public function testFlushOutput_continueBufferingFalse_releasesWriter()
	{
		$writer = new TShellWriter(new \Prado\IO\TTextWriter());
		$this->_app->setWriter($writer);

		$this->_app->flushOutput(false);

		$this->assertNull($this->_app->pubGetWriterDirect());
	}

	// -----------------------------------------------------------------------
	// printGreeting
	// -----------------------------------------------------------------------

	public function testPrintGreeting_setsHelpPrintedWhenNotQuiet()
	{
		$writer = new TShellWriter(new \Prado\IO\TTextWriter());
		$this->_app->pubSetHelpPrinted(false);
		$this->_app->setQuietMode(0);

		$this->_app->printGreeting($writer);

		$this->assertTrue($this->_app->pubIsHelpPrinted());
	}

	public function testPrintGreeting_doesNotSetHelpPrintedWhenQuiet()
	{
		$writer = new TShellWriter(new \Prado\IO\TTextWriter());
		$this->_app->pubSetHelpPrinted(false);
		$this->_app->setQuietMode(1);

		$this->_app->printGreeting($writer);

		$this->assertFalse($this->_app->pubIsHelpPrinted());
	}

	public function testPrintGreeting_guardPreventsDuplicatePrint()
	{
		$writer = new TShellWriter(new \Prado\IO\TTextWriter());
		$this->_app->pubSetHelpPrinted(true); // already printed
		$this->_app->setQuietMode(0);

		// Must not throw — just a no-op.
		$this->_app->printGreeting($writer);

		$this->assertTrue($this->_app->pubIsHelpPrinted());
	}

	// -----------------------------------------------------------------------
	// processArguments
	// -----------------------------------------------------------------------

	public function testProcessArguments_stripsRegisteredOption()
	{
		$captured = null;
		$this->_app->registerOption('test-opt', function ($v) use (&$captured) { $captured = $v; }, 'Test option');
		$this->_app->pubSetArguments(['--test-opt', 'remaining']);

		$this->_app->processArguments($this->_app, null);

		$this->assertSame('', $captured);
		$remaining = $this->_app->pubGetArguments();
		$this->assertNotContains('--test-opt', $remaining);
		$this->assertContains('remaining', $remaining);
	}

	public function testProcessArguments_stripsOptionWithEqualsSyntax()
	{
		$captured = null;
		$this->_app->registerOption('test-opt', function ($v) use (&$captured) { $captured = $v; }, 'Test option');
		$this->_app->pubSetArguments(['--test-opt=hello', 'remaining']);

		$this->_app->processArguments($this->_app, null);

		$this->assertSame('hello', $captured);
		$this->assertNotContains('--test-opt=hello', $this->_app->pubGetArguments());
		$this->assertContains('remaining', $this->_app->pubGetArguments());
	}

	public function testProcessArguments_stripsAlias()
	{
		$captured = null;
		$this->_app->registerOption('test-opt', function ($v) use (&$captured) { $captured = $v; }, 'Test option');
		$this->_app->registerOptionAlias('t', 'test-opt');
		$this->_app->pubSetArguments(['-t', 'remaining']);

		$this->_app->processArguments($this->_app, null);

		$this->assertSame('', $captured);
		$remaining = $this->_app->pubGetArguments();
		$this->assertNotContains('-t', $remaining);
		$this->assertContains('remaining', $remaining);
	}

	public function testProcessArguments_preservesUnrecognizedArg()
	{
		$this->_app->pubSetArguments(['unrecognized-arg']);

		$this->_app->processArguments($this->_app, null);

		$this->assertContains('unrecognized-arg', $this->_app->pubGetArguments());
	}

	public function testProcessArguments_installsShellActions()
	{
		$this->_app->pubSetArguments([]);

		$this->_app->processArguments($this->_app, null);

		// installShellActions() is called; at minimum THelpAction must be registered.
		$this->assertTrue($this->_app->hasShellActionClass(THelpAction::class));
	}

	// -----------------------------------------------------------------------
	// processActionArguments
	// -----------------------------------------------------------------------

	public function testProcessActionArguments_stripsOption()
	{
		$action = new ShellTestAction();
		$args = ['test', '--verbose'];

		$this->_app->processActionArguments($args, $action, 'run');

		$this->assertSame('', $action->verbose);
		$this->assertNotContains('--verbose', $args);
		$this->assertContains('test', $args);
	}

	public function testProcessActionArguments_stripsOptionWithEqualsSyntax()
	{
		$action = new ShellTestAction();
		$args = ['test', '--verbose=3'];

		$this->_app->processActionArguments($args, $action, 'run');

		$this->assertSame('3', $action->verbose);
		$this->assertNotContains('--verbose=3', $args);
	}

	public function testProcessActionArguments_stripsAlias()
	{
		$action = new ShellTestAction();
		$args = ['test', '-v'];

		$this->_app->processActionArguments($args, $action, 'run');

		$this->assertSame('', $action->verbose);
		$this->assertNotContains('-v', $args);
	}

	public function testProcessActionArguments_preservesNonOptionArgs()
	{
		$action = new ShellTestAction();
		$args = ['test', 'positional-arg'];

		$this->_app->processActionArguments($args, $action, 'run');

		$this->assertContains('positional-arg', $args);
	}

	public function testProcessActionArguments_emptyOptions_returnsEarlyLeavingArgsUntouched()
	{
		$action = new ShellTestNoOptionAction();
		$args = ['noop', '--verbose', 'other'];

		$this->_app->processActionArguments($args, $action, 'run');

		// No options recognised — args must be unchanged.
		$this->assertContains('--verbose', $args);
		$this->assertContains('other', $args);
	}

	public function testProcessActionArguments_reindexesArgs()
	{
		$action = new ShellTestAction();
		$args = ['test', '--verbose', 'positional'];

		$this->_app->processActionArguments($args, $action, 'run');

		// After stripping --verbose, the remaining args must be a contiguous
		// zero-indexed array (no gaps from unset()).
		$this->assertSame(array_values($args), $args);
	}
}
