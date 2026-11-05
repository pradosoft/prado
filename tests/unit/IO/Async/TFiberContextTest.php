<?php

namespace Prado\Test\Unit\IO\Async;

use Prado\IO\Async\TAsync;
use Prado\IO\Async\TFiberContext;

class TFiberContextTest extends \PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		TFiberContext::clear();
	}

	protected function tearDown(): void
	{
		TFiberContext::clear();
	}

	public function testMainContextRoundTrips(): void
	{
		self::assertFalse(TFiberContext::has('k'));
		self::assertSame('d', TFiberContext::get('k', 'd'));
		TFiberContext::set('k', 'v');
		self::assertTrue(TFiberContext::has('k'));
		self::assertSame('v', TFiberContext::get('k'));
		self::assertSame(['k' => 'v'], TFiberContext::all());
		TFiberContext::remove('k');
		self::assertSame([], TFiberContext::all());
		TFiberContext::replace(['a' => 1]);
		self::assertSame(1, TFiberContext::get('a'));
	}

	public function testAWorkerFiberHasItsOwnContext(): void
	{
		TFiberContext::set('who', 'main');
		$fiber = new \Fiber(function (): array {
			$before = TFiberContext::get('who');
			TFiberContext::set('who', 'fiber');
			return [$before, TFiberContext::get('who')];
		});
		$fiber->start();
		self::assertSame([null, 'fiber'], $fiber->getReturn(), 'A raw fiber starts empty and writes stay inside it.');
		self::assertSame('main', TFiberContext::get('who'), 'The main context is untouched.');
	}

	public function testRunCopiesTheContextIntoTheNewFiber(): void
	{
		TFiberContext::set('user', 'alice');
		$future = TAsync::run(function (): string {
			$seen = TFiberContext::get('user');
			TFiberContext::set('user', 'bob');
			return $seen . '/' . TFiberContext::get('user');
		});
		self::assertSame('alice/bob', $future->await(), 'The child starts with the parent context and changes locally.');
		self::assertSame('alice', TFiberContext::get('user'), 'The parent context is untouched.');
	}
}
