<?php

namespace Prado\Test\Unit\Shell;

use PHPUnit\Framework\TestCase;
use Prado\Shell\TShellAction;
use Prado\Shell\TShellApplication;
use Prado\Shell\TShellWriter;
use Prado\Test\Unit\Harness\IO\TTestCapturingTextWriter;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\Harness\TTestShellApplication;
use Prado\Util\Helpers\TProcessHelper;

/**
 * A shell action whose name holds a regular expression metacharacter.
 */
class TShellActionTestDottedAction extends TShellAction
{
	protected $action = 'my.cmd';
	protected $methods = ['index', 'run'];
	protected $parameters = [null, null];
	protected $optional = [null, null];
	protected $description = ['Dotted action', 'Index', 'Run'];
}

/**
 * A shell action whose default method is not `index`.
 */
class TShellActionTestRunAction extends TShellAction
{
	protected $action = 'job';
	protected $defaultMethod = 1;
	protected $methods = ['index', 'run'];
	protected $parameters = [null, null];
	protected $optional = [null, null];
	protected $description = ['Job action', 'Index', 'Run'];
}

/**
 * A shell action with required and optional parameters, given as a string or a list,
 * and public wrappers of the file helpers.
 */
class TShellActionTestParamAction extends TShellAction
{
	protected $action = 'param';
	protected $methods = ['index', 'get', 'set'];
	protected $parameters = [null, 'key', ['key', 'value']];
	protected $optional = [null, 'extra', ['first', 'second']];
	protected $description = ['Param action', 'Index method', 'Get method', 'Set method'];

	public function callCreateDirectory(string $dir, int $mask): void
	{
		$this->createDirectory($dir, $mask);
	}

	public function callCreateFile(string $filename, string $content): void
	{
		$this->createFile($filename, $content);
	}
}

/**
 * A shell action with one method.
 */
class TShellActionTestSingleAction extends TShellAction
{
	protected $action = 'single';
	protected $methods = ['index'];
	protected $parameters = [null];
	protected $optional = [null];
	protected $description = ['Single action', 'Index method'];
}

/**
 * A shell action without methods.
 */
class TShellActionTestNoMethodAction extends TShellAction
{
	protected $action = 'none';
	protected $methods = [];
	protected $parameters = [];
	protected $optional = [];
	protected $description = ['No methods'];
}

/**
 * Tests for {@see TShellAction}.
 */
class TShellActionTest extends TestCase
{
	public function testIsValidActionMatchesTheActionNameLiterally(): void
	{
		$action = new TShellActionTestDottedAction();

		$this->assertSame('index', $action->isValidAction(['my.cmd']));
		$this->assertSame('run', $action->isValidAction(['my.cmd/run']));
		$this->assertNull($action->isValidAction(['myxcmd']));
		$this->assertNull($action->isValidAction(['myxcmd/run']));
	}

	public function testGetExampleNamesTheDefaultMethod(): void
	{
		$this->assertSame('my.cmd', (new TShellActionTestDottedAction())->getExample());
		$this->assertSame('job/run', (new TShellActionTestRunAction())->getExample());
	}

	private function writer(TTestCapturingTextWriter $text): TShellWriter
	{
		$writer = new TShellWriter($text);
		$writer->setColorSupported(false);
		$writer->setWidth(200);
		return $writer;
	}

	public function testGetApplicationIsTheShellApplicationOnly(): void
	{
		$action = new TShellActionTestSingleAction();

		$app = new TTestApplication();
		try {
			$this->assertNull($action->getApplication());
		} finally {
			$app->restoreApplication();
		}

		$app = new TTestShellApplication();
		try {
			$this->assertInstanceOf(TShellApplication::class, $action->getApplication());
			$this->assertSame($app, $action->getApplication());
		} finally {
			$app->restoreApplication();
		}
	}

	public function testWriterAndAction(): void
	{
		$action = new TShellActionTestSingleAction();
		$writer = $this->writer(new TTestCapturingTextWriter());

		$action->setWriter($writer);
		$this->assertSame($writer, $action->getWriter());

		$this->assertSame('single', $action->getAction());
		$action->setAction('renamed');
		$this->assertSame('renamed', $action->getAction());
		$this->assertSame('renamed', $action->getExample());
	}

	public function testOptionsAndAliasesAreEmpty(): void
	{
		$action = new TShellActionTestSingleAction();

		$this->assertSame([], $action->options('index'));
		$this->assertSame([], $action->optionAliases());
	}

	public function testIsValidActionNeedsTheRequiredParameters(): void
	{
		$action = new TShellActionTestParamAction();

		$this->assertSame('index', $action->isValidAction(['param']));
		$this->assertSame('index', $action->isValidAction(['param/']));
		$this->assertNull($action->isValidAction(['param/get']));
		$this->assertSame('get', $action->isValidAction(['param/get', 'k']));
		$this->assertNull($action->isValidAction(['param/set', 'k']));
		$this->assertSame('set', $action->isValidAction(['param/set', 'k', 'v']));
		$this->assertNull($action->isValidAction(['param/missing']));
		$this->assertNull($action->isValidAction([]));
	}

	public function testRenderHelpCommandListsEachMethodWithItsParameters(): void
	{
		$action = new TShellActionTestParamAction();
		$action->setWriter($this->writer($text = new TTestCapturingTextWriter()));

		$action->renderHelpCommand('param');

		$lines = explode("\n", $text->getCaptured());
		$this->assertContains('usage: prado-cli param/<method>', $lines);
		$this->assertContains('example: prado-cli param/index', $lines);
		$this->assertContains('  param/index ', $lines);
		$this->assertContains('  param/get <key> [extra]', $lines);
		$this->assertContains('  param/set <key> <value> [first] [second]', $lines);
		$this->assertContains('         Get method', $lines);
	}

	public function testRenderHelpCommandOfOneMethod(): void
	{
		$action = new TShellActionTestSingleAction();
		$action->setWriter($this->writer($text = new TTestCapturingTextWriter()));

		$action->renderHelpCommand('single');

		$this->assertStringContainsString("  single/index \n         Index method\n", $text->getCaptured());
	}

	public function testRenderHelpListsTheActionAndItsMethods(): void
	{
		$action = new TShellActionTestParamAction();
		$action->setWriter($this->writer(new TTestCapturingTextWriter()));

		$lines = explode(PHP_EOL, $action->renderHelp());

		$this->assertSame(str_pad('  param', 31) . 'Param action', $lines[0]);
		$this->assertSame(str_pad('     param/index', 31) . 'Index method', $lines[1]);
		$this->assertSame(str_pad('     param/set', 31) . 'Set method', $lines[3]);
		$this->assertCount(5, $lines);
	}

	public function testRenderHelpOfOneMethod(): void
	{
		$action = new TShellActionTestSingleAction();
		$action->setWriter($this->writer(new TTestCapturingTextWriter()));

		$this->assertSame(
			str_pad('  single', 31) . 'Single action' . PHP_EOL . str_pad('     single/index', 31) . 'Index method' . PHP_EOL,
			$action->renderHelp()
		);
	}

	/**
	 * Asserts the permission bits of `$dir`; Windows has no POSIX directory modes.
	 */
	private function assertDirectoryMode(int $mode, string $dir): void
	{
		if (TProcessHelper::isSystemWindows()) {
			return;
		}
		clearstatcache();
		$this->assertSame($mode, fileperms($dir) & 0o777);
	}

	public function testCreateDirectoryCreatesItOnceAndSetsItsMode(): void
	{
		$action = new TShellActionTestParamAction();
		$action->setWriter($this->writer($text = new TTestCapturingTextWriter()));
		$dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'shell_action_' . uniqid();
		try {
			$action->callCreateDirectory($dir, 0o700);

			$this->assertDirectoryExists($dir);
			$this->assertSame("creating $dir\n", $text->getCaptured());
			$this->assertDirectoryMode(0o700, $dir);

			$action->callCreateDirectory($dir, 0o750);

			$this->assertSame("creating $dir\n", $text->getCaptured());
			$this->assertDirectoryMode(0o750, $dir);
		} finally {
			@rmdir($dir);
		}
	}

	public function testCreateFileWritesANewFileOnly(): void
	{
		$action = new TShellActionTestParamAction();
		$action->setWriter($this->writer($text = new TTestCapturingTextWriter()));
		$file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'shell_action_' . uniqid() . '.txt';
		try {
			$action->callCreateFile($file, 'first');

			$this->assertStringEqualsFile($file, 'first');
			$this->assertSame("creating $file\n", $text->getCaptured());

			$action->callCreateFile($file, 'second');

			$this->assertStringEqualsFile($file, 'first');
			$this->assertSame("creating $file\n", $text->getCaptured());
		} finally {
			@unlink($file);
		}
	}

	public function testRenderHelpWithoutMethods(): void
	{
		$action = new TShellActionTestNoMethodAction();
		$action->setWriter($this->writer(new TTestCapturingTextWriter()));

		$this->assertSame(str_pad('  none', 31) . 'No methods' . PHP_EOL, $action->renderHelp());
	}
}
