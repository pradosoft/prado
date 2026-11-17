<?php

/**
 * TShellApplicationTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Shell;

use Prado\Caching\ICache;
use Prado\IO\ITextWriter;
use Prado\IO\TMemoryStream;
use Prado\IO\TStdErrStream;
use Prado\IO\TStream;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\Shell\Actions\TFlushCachesAction;
use Prado\Shell\Actions\THelpAction;
use Prado\Shell\Actions\TPhpShellAction;
use Prado\Shell\Actions\TWebServerAction;
use Prado\Shell\TShellApplication;
use Prado\Shell\TShellWriter;
use Prado\TApplicationMode;
use Prado\TModule;
use Prado\Test\Unit\Harness\IO\TTestCapturingTextWriter;
use Prado\Test\Unit\Harness\TTestShellApplication;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Helpers\TProcessHelper;

/**
 * A shell application whose error stream is a memory stream.
 */
class ShellTestMemoryErrorApplication extends TTestShellApplication
{
	public ?TMemoryStream $errors = null;

	protected function createStdErrStream(): ?TStream
	{
		return $this->errors = new TMemoryStream();
	}
}

/**
 * A shell application that captures its output and records its exit code.
 */
class ShellTestRunApplication extends TTestShellApplication
{
	public ?TTestCapturingTextWriter $text = null;
	public ?int $exitCode = null;

	protected function createStdOutWriter(): ITextWriter
	{
		return $this->text ??= new TTestCapturingTextWriter();
	}

	protected function exit(int $exitCode): void
	{
		$this->exitCode = $exitCode;
	}
}

/**
 * A minimal ICache+TModule stub used to verify that {@see TShellApplication::installShellActions()}
 * registers {@see TFlushCachesAction} only when at least one {@see ICache} module is present.
 */
class ShellTestCacheModule extends TModule implements ICache
{
	public function init($config) {}
	public static function getIsAvailable(): bool { return true; }
	public function get($id) { return false; }
	public function set($id, $value, $expire = 0, $dependency = null) { return true; }
	public function add($id, $value, $expire = 0, $dependency = null) { return true; }
	public function delete($id) { return true; }
	public function flush() { return true; }
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
 * A shell action whose `missing` method has no action method, and whose `fail`
 * method reports the command as not handled.
 */
class ShellTestPartialAction extends \Prado\Shell\TShellAction
{
	protected $action = 'partial';
	protected $methods = ['missing', 'fail'];
	protected $parameters = [null, null];
	protected $optional = [null, null];
	protected $description = ['Partial action', 'Has no action method', 'Is not handled'];

	public function actionFail($args): bool
	{
		return false;
	}
}

/**
 * A shell action without an example in the general help.
 */
class ShellTestNoExampleAction extends ShellTestNoOptionAction
{
	public function getExample(): ?string
	{
		return null;
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
class TShellApplicationTest extends \PHPUnit\Framework\TestCase
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
	// hasActiveRecordConfig (deprecated; Active Record is the prado-activerecord package)
	// -----------------------------------------------------------------------

	public function testHasActiveRecordConfigReturnsFalseWithoutModule()
	{
		$this->assertFalse($this->_app->hasActiveRecordConfig());
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

	public function testProcessActionArguments_keepsEqualsSignsInTheAliasValue()
	{
		$action = new ShellTestAction();
		$args = ['test', '-v=c=d'];

		$this->_app->processActionArguments($args, $action, 'run');

		$this->assertSame('c=d', $action->verbose);
		$this->assertSame(['test'], $args);
	}

	public function testProcessActionArguments_keepsEqualsSignsInTheOptionValue()
	{
		$action = new ShellTestAction();
		$args = ['test', '--verbose=a=b'];

		$this->_app->processActionArguments($args, $action, 'run');

		$this->assertSame('a=b', $action->verbose);
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

	// -----------------------------------------------------------------------
	// Error stream
	// -----------------------------------------------------------------------

	public function testCreateStdErrStreamIsTheStandardError()
	{
		$method = new \ReflectionMethod(TShellApplication::class, 'createStdErrStream');

		$this->assertInstanceOf(TStdErrStream::class, $method->invoke($this->_app));
	}

	public function testTheWriterSendsErrorBlocksToTheErrorStream()
	{
		$this->_app->restoreApplication();
		$this->_app = $app = new ShellTestMemoryErrorApplication();
		$text = new TTestCapturingTextWriter();

		$writer = $app->getWriter();
		$writer->setWriter($text);
		$writer->write('before');
		$writer->writeError('boom');

		$this->assertSame($app->errors, $writer->getErrorStream());
		$this->assertSame('before', $text->getCaptured());
		$this->assertSame($writer->formatError('boom'), (string) $app->errors);
	}

	public function testTheHarnessKeepsErrorBlocksInTheOutputWriter()
	{
		$this->assertNull($this->_app->getWriter()->getErrorStream());
	}

	// -----------------------------------------------------------------------
	// Quiet mode levels
	// -----------------------------------------------------------------------

	public function testQuietModeLevelConstants()
	{
		$this->assertSame(TShellApplication::QUIET_NONE, $this->_app->getQuietMode());
		$this->_app->setQuietMode('');
		$this->assertSame(TShellApplication::QUIET_INFO, $this->_app->getQuietMode());
		$this->_app->setQuietMode(99);
		$this->assertSame(TShellApplication::QUIET_ERRORS, $this->_app->getQuietMode());
		$this->_app->setQuietMode(-1);
		$this->assertSame(TShellApplication::QUIET_NONE, $this->_app->getQuietMode());
		$this->assertSame([0, 1, 2, 3], [TShellApplication::QUIET_NONE, TShellApplication::QUIET_INFO, TShellApplication::QUIET_OUTPUT, TShellApplication::QUIET_ERRORS]);
	}

	// -----------------------------------------------------------------------
	// printHelp
	// -----------------------------------------------------------------------

	public function testPrintHelpShowsAnExampleOfEachRegisteredAction()
	{
		$this->_app->addShellActionClass(THelpAction::class);
		$this->_app->addShellActionClass(TWebServerAction::class);
		$this->_app->addShellActionClass(TFlushCachesAction::class);
		$text = new TTestCapturingTextWriter();

		$this->_app->printHelp(new TShellWriter($text));

		$output = $text->getCaptured();
		$this->assertStringContainsString("usage: prado-cli <command>[/<method>] <parameter> [optional]\n", $output);
		$this->assertStringContainsString("example: prado-cli help\nexample: prado-cli http/serve\nexample: prado-cli cache/flush-all\n", $output);
		$this->assertStringContainsString('Loads the application.xml or application.php from <folder>', $output);
		$this->assertStringNotContainsString('cron/tasks', $output);
		$this->assertStringNotContainsString('prado-cli.php', $output);
	}

	public function testPrintHelpWithoutActionsShowsNoExample()
	{
		$text = new TTestCapturingTextWriter();

		$this->_app->printHelp(new TShellWriter($text));

		$this->assertStringNotContainsString('example:', $text->getCaptured());
	}

	// -----------------------------------------------------------------------
	// run()
	// -----------------------------------------------------------------------

	/**
	 * Runs a {@see ShellTestRunApplication} with `$args` in place of the test application.
	 */
	private function runApplication(?array $args): ShellTestRunApplication
	{
		$this->_app->restoreApplication();
		$app = new ShellTestRunApplication();
		try {
			$app->run($args);
		} finally {
			$app->restoreApplication();
			$this->_app = new TTestShellApplication();
		}
		return $app;
	}

	public function testRunWithoutArgumentsReadsTheCommandLine(): void
	{
		$argv = $_SERVER['argv'] ?? null;
		$_SERVER['argv'] = ['prado-cli', '-q', 'help', 'help'];
		try {
			$app = $this->runApplication(null);
		} finally {
			$_SERVER['argv'] = $argv;
		}

		$this->assertSame(1, $app->getQuietMode());
		$this->assertSame(['help', 'help'], $app->pubGetArguments());
		$this->assertStringContainsString('usage: prado-cli help/<method>', $app->text->getCaptured());
	}

	public function testRunWithoutACommandPrintsTheHelp(): void
	{
		$app = $this->runApplication(['prado-cli']);

		$captured = $app->text->getCaptured();
		$this->assertStringContainsString('Command line tools for Prado ' . Prado::getVersion(), $captured);
		$this->assertStringContainsString('The following commands are available:', $captured);
		$this->assertStringContainsString(str_pad('  -q=<level>', 20) . ' is an alias for --quiet', $captured);
		$this->assertNull($app->exitCode);
	}

	public function testRunQuietWithAnUnknownCommandPrintsNothing(): void
	{
		$app = $this->runApplication(['prado-cli', '--quiet', 'unknown']);

		$this->assertSame(1, $app->getQuietMode());
		$this->assertSame('', $app->text->getCaptured());
	}

	public function testRunMapsLangToTheAcceptedLanguageAndCharset(): void
	{
		$saved = [];
		foreach (['LANG', 'HTTP_ACCEPT_LANGUAGE', 'HTTP_ACCEPT_CHARSET'] as $key) {
			$saved[$key] = $_SERVER[$key] ?? null;
			unset($_SERVER[$key]);
		}
		try {
			$_SERVER['LANG'] = 'fr_CA.ISO-8859-1';
			$this->runApplication(['prado-cli', '-q']);
			$this->assertSame('fr_CA', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			$this->assertSame('ISO-8859-1', $_SERVER['HTTP_ACCEPT_CHARSET']);

			unset($_SERVER['HTTP_ACCEPT_CHARSET']);
			$_SERVER['LANG'] = 'de_DE';
			$this->runApplication(['prado-cli', '-q']);
			$this->assertSame('de_DE', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			$this->assertArrayNotHasKey('HTTP_ACCEPT_CHARSET', $_SERVER);

			unset($_SERVER['LANG'], $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			$this->runApplication(['prado-cli', '-q']);
			$this->assertArrayNotHasKey('HTTP_ACCEPT_LANGUAGE', $_SERVER);
		} finally {
			foreach ($saved as $key => $value) {
				if ($value === null) {
					unset($_SERVER[$key]);
				} else {
					$_SERVER[$key] = $value;
				}
			}
		}
	}

	public function testDetectCronTabShellWithoutAnInteractiveTerm(): void
	{
		$term = getenv('TERM');
		try {
			putenv('TERM');
			$this->assertTrue(TShellApplication::detectCronTabShell());
			putenv('TERM=unknown');
			$this->assertTrue(TShellApplication::detectCronTabShell());
			putenv('TERM=xterm');
			$this->assertFalse(TShellApplication::detectCronTabShell());
		} finally {
			putenv($term === false ? 'TERM' : "TERM=$term");
		}
	}

	// -----------------------------------------------------------------------
	// runService()
	// -----------------------------------------------------------------------

	/**
	 * Runs {@see TShellApplication::runService()} with `$args` and returns the output.
	 */
	private function runService(array $args, int $quiet = TShellApplication::QUIET_NONE): string
	{
		$this->_app->setWriter($writer = new TShellWriter($text = new TTestCapturingTextWriter()));
		$writer->setColorSupported(false);
		$this->_app->setQuietMode($quiet);
		$this->_app->addShellActionClass(ShellTestAction::class);
		$this->_app->addShellActionClass(ShellTestPartialAction::class);
		$this->_app->pubSetArguments($args);
		$this->_app->runService();
		return $text->getCaptured();
	}

	public function testRunServiceRunsTheMatchingAction(): void
	{
		$captured = $this->runService(['test', '--verbose=yes']);

		$this->assertSame('yes', $this->_app->getShellActions()[ShellTestAction::class]->verbose);
		$this->assertStringContainsString('Command line tools for Prado', $captured);
		$this->assertStringNotContainsString('The following commands are available:', $captured);
	}

	public function testRunServiceReportsAMethodWithoutAnActionMethod(): void
	{
		$captured = $this->runService(['partial/missing']);

		$this->assertStringContainsString('missing is not an available command', $captured);
		$this->assertStringNotContainsString('The following commands are available:', $captured);
	}

	public function testRunServicePrintsTheHelpForAnUnhandledCommand(): void
	{
		$this->assertStringContainsString('The following commands are available:', $this->runService(['partial/fail']));
	}

	public function testRunServiceQuietPrintsNoHelp(): void
	{
		$this->assertSame('', $this->runService(['partial/fail'], TShellApplication::QUIET_INFO));
	}

	// -----------------------------------------------------------------------
	// printHelp()
	// -----------------------------------------------------------------------

	public function testPrintHelpSkipsAnActionWithoutAnExampleAndNamesAnAliasWithoutAnOption(): void
	{
		$this->_app->addShellActionClass(ShellTestNoExampleAction::class);
		$this->_app->registerOptionAlias('z', 'unregistered');
		$writer = new TShellWriter($text = new TTestCapturingTextWriter());
		$writer->setColorSupported(false);

		$this->_app->printHelp($writer);

		$captured = $text->getCaptured();
		$this->assertStringNotContainsString('example: prado-cli', $captured);
		$this->assertStringContainsString(str_pad('  -z', 20) . ' is an alias for --unregistered', $captured);
		$this->assertStringContainsString('noop/run', $captured);
	}

	public function testInstallShellActionsWithACacheAndTheWebServer(): void
	{
		$this->_app->setModule('cache', new ShellTestCacheModule());
		$this->_app->setMode(TApplicationMode::Debug);

		$this->_app->installShellActions();

		$this->assertTrue($this->_app->hasShellActionClass(TFlushCachesAction::class));
		$this->assertTrue($this->_app->hasShellActionClass(TWebServerAction::class));
	}

	// -----------------------------------------------------------------------
	// resolveRuntimePath()
	// -----------------------------------------------------------------------

	/**
	 * Returns the temporary-directory runtime path of `$basePath`.
	 */
	private function tempRuntimePath(string $basePath): string
	{
		return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prado-' . sha1($basePath) . DIRECTORY_SEPARATOR . TShellApplication::SHELL_RUNTIME_PATH;
	}

	/**
	 * Calls `resolveRuntimePath()` on a new temporary base directory and removes the
	 * directories it creates.
	 * @param callable $prepare called with the base path before resolving.
	 * @return array{0: string, 1: string} the base path and the resolved runtime path.
	 */
	private function resolveRuntimePath(callable $prepare, ?string $configFile = null): array
	{
		$basePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'shell_runtime_' . uniqid();
		mkdir($basePath);
		$temp = $this->tempRuntimePath($basePath);
		try {
			$prepare($basePath);
			return [$basePath, PradoUnit::invoke($this->_app, 'resolveRuntimePath', $basePath, $configFile)];
		} finally {
			chmod($basePath, 0o777);
			$versioned = $basePath . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'application.xml-' . Prado::getVersion();
			foreach ([$versioned, $basePath . DIRECTORY_SEPARATOR . 'runtime', $basePath . DIRECTORY_SEPARATOR . TShellApplication::SHELL_RUNTIME_PATH, $temp] as $dir) {
				if (is_dir($dir)) {
					chmod($dir, 0o777);
					rmdir($dir);
				}
			}
			$path = dirname($temp);
			if (is_file($path)) {
				unlink($path);
			} elseif (is_dir($path)) {
				rmdir($path);
			}
			rmdir($basePath);
		}
	}

	public function testResolveRuntimePathWithAConfigFileIsTheApplicationRuntime(): void
	{
		[$basePath, $runtimePath] = $this->resolveRuntimePath(function ($basePath) {
			mkdir($basePath . DIRECTORY_SEPARATOR . 'runtime');
		}, 'application.xml');

		$this->assertSame($basePath . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'application.xml-' . Prado::getVersion(), $runtimePath);
	}

	public function testResolveRuntimePathCreatesTheHiddenRuntime(): void
	{
		[$basePath, $runtimePath] = $this->resolveRuntimePath(function ($basePath) {});

		$this->assertSame($basePath . DIRECTORY_SEPARATOR . TShellApplication::SHELL_RUNTIME_PATH, $runtimePath);
	}

	public function testResolveRuntimePathKeepsAWritableHiddenRuntime(): void
	{
		[$basePath, $runtimePath] = $this->resolveRuntimePath(function ($basePath) {
			mkdir($basePath . DIRECTORY_SEPARATOR . TShellApplication::SHELL_RUNTIME_PATH);
		});

		$this->assertSame($basePath . DIRECTORY_SEPARATOR . TShellApplication::SHELL_RUNTIME_PATH, $runtimePath);
	}

	public function testResolveRuntimePathUsesTheTempDirectoryForAReadOnlyHiddenRuntime(): void
	{
		[$basePath, $runtimePath] = $this->resolveRuntimePath(function ($basePath) {
			mkdir($dir = $basePath . DIRECTORY_SEPARATOR . TShellApplication::SHELL_RUNTIME_PATH);
			chmod($dir, 0o555);
		});

		$this->assertSame($this->tempRuntimePath($basePath), $runtimePath);
	}

	public function testResolveRuntimePathUsesTheTempDirectoryForAReadOnlyBasePath(): void
	{
		if (TProcessHelper::isSystemWindows()) {
			$this->markTestSkipped('A read-only Windows directory still allows creating a directory inside it.');
		}
		[$basePath, $runtimePath] = $this->resolveRuntimePath(function ($basePath) {
			chmod($basePath, 0o555);
		});

		$this->assertSame($this->tempRuntimePath($basePath), $runtimePath);
	}

	public function testResolveRuntimePathKeepsAnExistingTempRuntime(): void
	{
		if (TProcessHelper::isSystemWindows()) {
			$this->markTestSkipped('A read-only Windows directory still allows creating a directory inside it.');
		}
		[$basePath, $runtimePath] = $this->resolveRuntimePath(function ($basePath) {
			chmod($basePath, 0o555);
			mkdir($this->tempRuntimePath($basePath), 0o777, true);
		});

		$this->assertSame($this->tempRuntimePath($basePath), $runtimePath);
	}

	public function testResolveRuntimePathThrowsWithoutAWritableDirectory(): void
	{
		if (TProcessHelper::isSystemWindows()) {
			$this->markTestSkipped('A read-only Windows directory still allows creating a directory inside it.');
		}
		$this->expectException(TConfigurationException::class);

		$this->resolveRuntimePath(function ($basePath) {
			chmod($basePath, 0o555);
			touch(dirname($this->tempRuntimePath($basePath)));
		});
	}

	public function testResolveRuntimePathKeepsAnExistingTempRuntimeForAReadOnlyHiddenRuntime(): void
	{
		[$basePath, $runtimePath] = $this->resolveRuntimePath(function ($basePath) {
			mkdir($dir = $basePath . DIRECTORY_SEPARATOR . TShellApplication::SHELL_RUNTIME_PATH);
			chmod($dir, 0o555);
			mkdir($this->tempRuntimePath($basePath), 0o777, true);
		});

		$this->assertSame($this->tempRuntimePath($basePath), $runtimePath);
	}

	public function testResolveRuntimePathThrowsForAReadOnlyHiddenRuntimeWithoutATempDirectory(): void
	{
		$this->expectException(TConfigurationException::class);

		$this->resolveRuntimePath(function ($basePath) {
			mkdir($dir = $basePath . DIRECTORY_SEPARATOR . TShellApplication::SHELL_RUNTIME_PATH);
			chmod($dir, 0o555);
			touch(dirname($this->tempRuntimePath($basePath)));
		});
	}
}
