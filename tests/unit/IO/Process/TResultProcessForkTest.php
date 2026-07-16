<?php

use Prado\IO\Process\TResultProcessFork;
use Prado\IO\Socket\TSocketReactor;
use Prado\Util\Helpers\TProcessHelper;

/** A result fork whose produce() returns without a callable, exercising the subclass hook. */
class FixedResultProcessFork extends TResultProcessFork
{
	protected function produce(): mixed
	{
		return ['from' => 'produce'];
	}
}

class TResultProcessForkTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		if (!TProcessHelper::isForkable()) {
			$this->markTestSkipped('pcntl forking is not available.');
		}
	}

	public function testChildResultIsReturnedToTheParent()
	{
		$fork = TResultProcessFork::fork(fn () => ['rows' => 42, 'ok' => true]);
		self::assertSame(0, $fork->wait());
		self::assertTrue($fork->getHasResult());
		self::assertSame(['rows' => 42, 'ok' => true], $fork->getResult());
	}

	public function testScalarResult()
	{
		$fork = TResultProcessFork::fork(fn () => 'done');
		$fork->wait();
		self::assertSame('done', $fork->getResult());
		self::assertTrue($fork->getHasResult());
	}

	public function testLargeResultDoesNotDeadlock()
	{
		$big = str_repeat('0123456789abcdef', 131072);   // 2 MiB, well past a pipe buffer
		$fork = TResultProcessFork::fork(fn () => $big);
		self::assertSame(0, $fork->wait(), 'A draining wait does not deadlock on a large result.');
		self::assertSame($big, $fork->getResult());
	}

	public function testProduceOverrideWithoutACallable()
	{
		$fork = FixedResultProcessFork::fork();
		$fork->wait();
		self::assertSame(['from' => 'produce'], $fork->getResult());
	}

	public function testNullResultWithSuccessIsDistinctFromFailure()
	{
		$fork = TResultProcessFork::fork(fn () => null);
		self::assertSame(0, $fork->wait());
		self::assertTrue($fork->getHasResult(), 'A successful null result still counts as received.');
		self::assertNull($fork->getResult());
	}

	public function testNestedStructureRoundTripsBySerialization()
	{
		$obj = new \stdClass();
		$obj->name = 'child';
		$fork = TResultProcessFork::fork(fn () => ['list' => [1, 2.5, 'three'], 'obj' => $obj, 'utf8' => 'héllo']);
		$fork->wait();
		$result = $fork->getResult();
		self::assertSame([1, 2.5, 'three'], $result['list']);
		self::assertSame('child', $result['obj']->name, 'An object round-trips through serialization.');
		self::assertSame('héllo', $result['utf8']);
	}

	public function testAnExceptionLeavesNoResultAndANonZeroExit()
	{
		$fork = TResultProcessFork::fork(function () {
			throw new \RuntimeException('boom');
		});
		self::assertSame(TResultProcessFork::EXIT_EXCEPTION, $fork->wait());
		self::assertFalse($fork->getHasResult());
		self::assertNull($fork->getResult());
	}

	public function testResultCollectedThroughARegisteredReactor()
	{
		$reactor = new TSocketReactor();
		$fork = TResultProcessFork::fork(fn () => [1, 2, 3]);
		$exitCode = null;
		$fork->attachEventHandler('onExit', function ($sender, $code) use (&$exitCode) {
			$exitCode = $code;
		});
		$fork->register($reactor);

		$deadline = microtime(true) + 3.0;
		while ($fork->getState() !== TResultProcessFork::STATE_EXITED && microtime(true) < $deadline) {
			$reactor->tick(0.1);
		}
		self::assertSame([1, 2, 3], $fork->getResult());
		self::assertSame(0, $exitCode, 'onExit fires once the channel ends and the child is reaped.');
	}
}
