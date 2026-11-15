<?php

namespace Prado\Test\Unit\Shell;

use PHPUnit\Framework\TestCase;
use Prado\Shell\TShellAction;

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
}
