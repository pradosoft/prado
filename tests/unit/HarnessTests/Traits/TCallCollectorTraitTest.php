<?php

/**
 * TCallCollectorTraitTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

/**
 * Test double that records every call via {@see TCallCollectorTrait}.
 */
class CallCollectorDouble
{
	use TCallCollectorTrait;

	public function alpha($a, $b = 'default')
	{
		$this->collectCall();
		return 'alpha';
	}

	public function beta(...$args)
	{
		$this->collectCall();
		return 'beta';
	}

	public function noArgs()
	{
		$this->collectCall();
	}
}

/**
 * Tests for {@see TCallCollectorTrait}.
 *
 * @package System.Harness.Traits
 */
class TCallCollectorTraitTest extends PHPUnit\Framework\TestCase
{
	public function testRecordsCallingMethodNameAndArguments(): void
	{
		$double = new CallCollectorDouble();
		$double->alpha('x', 'y');

		$this->assertSame([['x', 'y']], $double->getCollectedCalls('alpha'));
	}

	public function testRecordsOnlyPassedArguments_defaultsNotFilled(): void
	{
		// PHP records the arguments actually passed; the omitted default is absent.
		$double = new CallCollectorDouble();
		$double->alpha('only');

		$this->assertSame([['only']], $double->getCollectedCalls('alpha'));
	}

	public function testRecordsVariadicArguments(): void
	{
		$double = new CallCollectorDouble();
		$double->beta(1, 2, 3);

		$this->assertSame([[1, 2, 3]], $double->getCollectedCalls('beta'));
	}

	public function testRecordsNoArgumentCall(): void
	{
		$double = new CallCollectorDouble();
		$double->noArgs();

		$this->assertSame([[]], $double->getCollectedCalls('noArgs'));
	}

	public function testSequentialLogPreservesOrderAcrossMethods(): void
	{
		$double = new CallCollectorDouble();
		$double->alpha('a');
		$double->beta('b');
		$double->alpha('c');

		$log = $double->getCollectedCalls();
		$this->assertSame(['alpha', 'beta', 'alpha'], array_column($log, 'method'));
	}

	public function testGetCollectedCallCount_totalAndPerMethod(): void
	{
		$double = new CallCollectorDouble();
		$double->alpha('a');
		$double->alpha('b');
		$double->beta('c');

		$this->assertSame(3, $double->getCollectedCallCount());
		$this->assertSame(2, $double->getCollectedCallCount('alpha'));
		$this->assertSame(1, $double->getCollectedCallCount('beta'));
		$this->assertSame(0, $double->getCollectedCallCount('missing'));
	}

	public function testGetCollectedCall_byIndex(): void
	{
		$double = new CallCollectorDouble();
		$double->alpha('a');

		$this->assertSame(['method' => 'alpha', 'args' => ['a']], $double->getCollectedCall(0));
		$this->assertNull($double->getCollectedCall(5));
	}

	public function testResetCollectedCalls_clearsLog(): void
	{
		$double = new CallCollectorDouble();
		$double->alpha('a');
		$double->resetCollectedCalls();

		$this->assertSame(0, $double->getCollectedCallCount());
		$this->assertSame([], $double->getCollectedCalls());
	}
}
