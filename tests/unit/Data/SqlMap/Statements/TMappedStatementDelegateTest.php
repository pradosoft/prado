<?php

namespace Prado\Test\Unit\Data\SqlMap\Statements;

use Prado\Data\SqlMap\Statements\TMappedStatement;

class TMappedStatementDelegateInvokable
{
	public array $calls = [];

	public function __invoke($sender, $param)
	{
		$this->calls[] = [$sender, $param];
	}
}

class TMappedStatementDelegateTest extends \PHPUnit\Framework\TestCase
{
	private function raise(TMappedStatement $statement, $handler, $param): void
	{
		$method = new \ReflectionMethod($statement, 'raiseRowDelegate');
		$method->invoke($statement, $handler, $param);
	}

	private function newStatement(): TMappedStatement
	{
		return (new \ReflectionClass(TMappedStatement::class))->newInstanceWithoutConstructor();
	}

	public function testInvokableObjectDelegate()
	{
		$statement = $this->newStatement();
		$handler = new TMappedStatementDelegateInvokable();
		$this->raise($statement, $handler, 'row');
		$this->assertSame([[$statement, 'row']], $handler->calls);
	}

	public function testClosureDelegate()
	{
		$statement = $this->newStatement();
		$calls = [];
		$this->raise($statement, function ($sender, $param) use (&$calls) {
			$calls[] = [$sender, $param];
		}, 'row');
		$this->assertSame([[$statement, 'row']], $calls);
	}

	public function testArrayDelegate()
	{
		$statement = $this->newStatement();
		$handler = new TMappedStatementDelegateInvokable();
		$this->raise($statement, [$handler, '__invoke'], 'row');
		$this->assertSame([[$statement, 'row']], $handler->calls);
	}
}
