<?php

namespace Prado\Test\Unit\IO\HttpClient;

use Prado\Exceptions\TCancelledException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Process\TProcessFork;
use Prado\IO\Async\TAsync;
use Prado\IO\Async\TCancellationToken;
use Prado\IO\Http\TRequest;
use Prado\IO\HttpClient\IHttpHandler;
use Prado\IO\HttpClient\TCurlMultiHttpHandler;
use Prado\IO\HttpClient\THttpClientOption;
use Prado\Prado;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Helpers\TProcessHelper;

class TCurlMultiHttpHandlerTest extends HttpHandlerConformanceTestCase
{
	protected function createHandler(): IHttpHandler
	{
		return new TCurlMultiHttpHandler();
	}

	public function testConcurrentTransfersInterleave(): void
	{
		$handler = new TCurlMultiHttpHandler();
		$slow = $handler->handleAsync(new TRequest('GET', self::url('/slow?delay=0.3')));
		$fast = $handler->handleAsync(new TRequest('GET', self::url('/')));
		self::assertSame(2, $handler->getPendingCount());
		[$a, $b] = TAsync::all([$slow, $fast])->await();
		self::assertSame('slow', (string) $a->getBody());
		self::assertSame('hello', (string) $b->getBody());
		self::assertSame(0, $handler->getPendingCount());
	}

	public function testFiberYieldsWhileWaiting(): void
	{
		$handler = new TCurlMultiHttpHandler();
		$order = [];
		$request = TAsync::run(function () use ($handler, &$order) {
			$order[] = 'request-start';
			$response = $handler->handle(new TRequest('GET', self::url('/slow?delay=0.2')));
			$order[] = 'request-end';
			return (string) $response->getBody();
		});
		$other = TAsync::run(function () use (&$order) {
			$order[] = 'other';
		});
		self::assertSame('slow', $request->await());
		$other->await();
		self::assertSame(['request-start', 'other', 'request-end'], $order);
	}

	public function testTokenCancelsAPendingTransfer(): void
	{
		$handler = new TCurlMultiHttpHandler();
		$token = new TCancellationToken();
		$future = $handler->handleAsync(new TRequest('GET', self::url('/slow?delay=2')), [THttpClientOption::TOKEN => $token]);
		$token->cancel();
		self::assertSame(0, $handler->getPendingCount());
		self::assertTrue($future->isRejected());
	}

	public function testPollIntervalMustBePositive(): void
	{
		$handler = new TCurlMultiHttpHandler();
		$handler->setPollInterval('0.05');
		self::assertSame(0.05, $handler->getPollInterval());
		$this->expectException(TInvalidDataValueException::class);
		$handler->setPollInterval(0);
	}

	public function testMaxConnections(): void
	{
		$handler = new TCurlMultiHttpHandler();
		$handler->setMaxConnections('4');
		self::assertSame(4, $handler->getMaxConnections());
		$handler->setMaxConnections(-1);
		self::assertSame(0, $handler->getMaxConnections());
	}

	public function testPrepareForForkReleasesAnIdleMultiHandle(): void
	{
		$app = Prado::getApplication();
		$handler = new TCurlMultiHttpHandler();
		$handler->handle(new TRequest('GET', self::url('/')));
		self::assertNotNull(PradoUnit::getProp($handler, '_multi'));

		$app->raiseEvent(TProcessHelper::FX_PREPARE_FOR_FORK, $app, null);
		self::assertNull(PradoUnit::getProp($handler, '_multi'), 'An idle handler releases its multi handle before a fork');
		self::assertSame('hello', (string) $handler->handle(new TRequest('GET', self::url('/')))->getBody(), 'The next transfer creates a new handle');

		$future = $handler->handleAsync(new TRequest('GET', self::url('/slow?delay=0.1')));
		$app->raiseEvent(TProcessHelper::FX_PREPARE_FOR_FORK, $app, null);
		self::assertNotNull(PradoUnit::getProp($handler, '_multi'), 'A pending transfer keeps the handle');
		self::assertSame('slow', (string) $future->await()->getBody());
	}

	public function testAForkedChildExitsAfterTheHandlerWasUsed(): void
	{
		if (!TProcessHelper::isForkable()) {
			self::markTestSkipped('pcntl forking is not available.');
		}
		$handler = new TCurlMultiHttpHandler();
		$handler->handle(new TRequest('GET', self::url('/')));
		$fork = TProcessFork::fork(fn () => 0);
		$exit = TAsync::timeout(TAsync::run(fn () => $fork->wait()), 10.0);
		try {
			self::assertSame(0, $exit->await(), 'The child exits instead of waiting on libcurl threads it does not have');
		} catch (TCancelledException $e) {
			$fork->kill();
			self::fail('The forked child did not exit within 10 seconds.');
		}
	}
}
