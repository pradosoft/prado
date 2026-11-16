<?php

namespace Prado\Test\Unit\Shell\Actions;

use PHPUnit\Framework\TestCase;
use Prado\Prado;
use Prado\Shell\Actions\TPhpShellAction;
use Prado\Shell\TShellWriter;
use Prado\Test\Unit\Harness\IO\TTestCapturingTextWriter;
use Prado\Test\Unit\Harness\TTestShellApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A PsySH shell that records its bound object and returns from run() at once.
 */
class TPhpShellActionTestShell extends \Psy\Shell
{
	public mixed $bound = null;
	public int $runs = 0;

	public function __construct()
	{
	}

	public function setBoundObject($boundObject)
	{
		$this->bound = $boundObject;
	}

	public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
	{
		$this->runs++;
		return 0;
	}
}

/**
 * Runs {@see TPhpShellActionTestShell}, or reports PsySH as missing.
 */
class TPhpShellActionTestAction extends TPhpShellAction
{
	public bool $available = true;
	public ?TPhpShellActionTestShell $shell = null;

	protected function isShellAvailable(): bool
	{
		return $this->available;
	}

	protected function createShell(): \Psy\Shell
	{
		return $this->shell = new TPhpShellActionTestShell();
	}
}

/**
 * Tests for {@see TPhpShellAction}.
 */
class TPhpShellActionTest extends TestCase
{
	private TTestShellApplication $_app;
	private TTestCapturingTextWriter $_text;
	private TPhpShellActionTestAction $_action;

	protected function setUp(): void
	{
		$this->_app = new TTestShellApplication();
		$this->_text = new TTestCapturingTextWriter();
		$this->_action = new TPhpShellActionTestAction();
		$this->_action->setWriter(new TShellWriter($this->_text));
	}

	protected function tearDown(): void
	{
		$this->_app->restoreApplication();
	}

	public function testActionIndexRunsTheShellBoundToTheApplication(): void
	{
		$this->_action->getWriter()->write('pending');

		$this->assertTrue($this->_action->actionIndex(['shell']));

		$this->assertSame(Prado::getApplication(), $this->_action->shell->bound);
		$this->assertSame(1, $this->_action->shell->runs);
		$this->assertSame('pending', $this->_text->getCaptured());
		$this->assertSame('', $this->_text->flush());
	}

	public function testActionIndexWithoutPsyshNamesThePackage(): void
	{
		$this->_action->available = false;

		$this->assertTrue($this->_action->actionIndex(['shell']));

		$this->assertNull($this->_action->shell);
		$this->assertStringContainsString('composer require psy/psysh', $this->_text->getCaptured());
	}

	public function testIsShellAvailableFindsPsysh(): void
	{
		$method = new \ReflectionMethod(TPhpShellAction::class, 'isShellAvailable');

		$this->assertTrue($method->invoke(new TPhpShellAction()));
	}

	public function testCreateShellIsAPsyshShell(): void
	{
		$method = new \ReflectionMethod(TPhpShellAction::class, 'createShell');

		$this->assertInstanceOf(\Psy\Shell::class, $method->invoke(new TPhpShellAction()));
	}
}
