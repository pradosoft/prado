<?php

use Prado\Exceptions\TInvalidOperationException;
use Prado\IO\Process\TLiveProcessFork;
use Prado\Util\Helpers\TProcessHelper;

class TLiveProcessForkTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		if (!TProcessHelper::isForkable()) {
			$this->markTestSkipped('pcntl forking is not available.');
		}
	}

	public function testLiveUpdatesStreamToTheParent()
	{
		$fork = TLiveProcessFork::fork(function (TLiveProcessFork $self) {
			$self['a'] = 1;
			$self['b'] = ['nested' => true];
			$self['a'] = 2;        // overwrite
			unset($self['b']);     // remove
			return 0;
		});
		$fork->wait();   // pumps every update, then reaps

		self::assertSame(['a' => 2], $fork->getData());
		self::assertSame(2, $fork['a']);
		self::assertFalse(isset($fork['b']), 'An unset is streamed too.');
	}

	public function testOnDataFiresPerUpdate()
	{
		$ops = [];
		$fork = TLiveProcessFork::fork(function (TLiveProcessFork $self) {
			$self['x'] = 10;
			$self['y'] = 20;
			return 0;
		});
		$fork->attachEventHandler('onData', function ($sender, $op) use (&$ops) {
			$ops[] = $op;
		});
		$fork->wait();

		self::assertSame([['set', 'x', 10], ['set', 'y', 20]], $ops, 'Each mutation arrives as its own update.');
		self::assertSame(['x' => 10, 'y' => 20], $fork->getData());
	}

	public function testManyUpdatesDoNotDeadlock()
	{
		$fork = TLiveProcessFork::fork(function (TLiveProcessFork $self) {
			for ($i = 0; $i < 5000; $i++) {
				$self['i'] = $i;   // far more writes than a pipe buffer holds
			}
			return 0;
		});
		$fork->wait();
		self::assertSame(4999, $fork['i'], 'The last streamed value wins after draining.');
	}

	public function testAppendSyntaxStreamsWithMatchingKeys()
	{
		$fork = TLiveProcessFork::fork(function (TLiveProcessFork $self) {
			$self[] = 'first';
			$self[] = 'second';
			$self[5] = 'five';
			$self[] = 'six';
			return 0;
		});
		$fork->wait();
		self::assertSame(['first', 'second', 5 => 'five', 6 => 'six'], $fork->getData(), 'An append streams the key the child assigned.');
	}

	public function testWritingOnTheParentReceiverThrows()
	{
		$fork = TLiveProcessFork::fork(function (TLiveProcessFork $self) {
			$self['ok'] = 1;   // the child (sender) may write
			return 0;
		});

		// The returned handle is the parent (the receiver): a write is rejected, not silently mirrored.
		$threw = false;
		try {
			$fork['x'] = 1;
		} catch (TInvalidOperationException $e) {
			$threw = true;
		}
		$fork->wait();

		self::assertTrue($threw, 'A write on the parent receiver throws.');
		self::assertSame(['ok' => 1], $fork->getData(), 'The child write streamed; the rejected write left no trace.');
	}

	public function testUnsetOnTheParentReceiverThrows()
	{
		$fork = TLiveProcessFork::fork(fn () => 0);

		$threw = false;
		try {
			unset($fork['anything']);
		} catch (TInvalidOperationException $e) {
			$threw = true;
		}
		$fork->wait();

		self::assertTrue($threw, 'An unset on the parent receiver throws.');
	}
}
