<?php

namespace Prado\Test\Unit\IO\HttpClient;

use Prado\Exceptions\TCancelledException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Async\TAsync;
use Prado\IO\Async\TCancellationToken;
use Prado\IO\Async\TFuture;
use Prado\IO\HttpClient\TCurlMultiHttpClient;
use Prado\IO\HttpClient\THttpClient;
use Prado\IO\HttpClient\THttpClientException;
use Prado\IO\Process\TProcessFork;
use Prado\Prado;
use Prado\Test\Unit\PradoUnit;
use Prado\Util\Helpers\TProcessHelper;

/**
 * Tests for TCurlMultiHttpClient against PHP's built-in test server.
 *
 * @requires extension curl
 */
class TCurlMultiHttpClientTest extends \PHPUnit\Framework\TestCase
{
	use HttpServerTestTrait;

	public static function setUpBeforeClass(): void
	{
		self::startHttpServer();
	}

	public static function tearDownAfterClass(): void
	{
		self::stopHttpServer();
	}

	protected function tearDown(): void
	{
		TAsync::setLoop(null);
	}

	public function testAvailabilityAndTransports(): void
	{
		self::assertTrue(TCurlMultiHttpClient::getIsAvailable());
		self::assertNotContains(TCurlMultiHttpClient::class, THttpClient::TRANSPORTS, 'The async client is chosen explicitly');
	}

	public function testProperties(): void
	{
		$client = new TCurlMultiHttpClient();
		self::assertSame(TCurlMultiHttpClient::DEFAULT_POLL_INTERVAL, $client->getPollInterval());
		$client->setPollInterval('0.5');
		self::assertSame(0.5, $client->getPollInterval());
		self::assertSame(0, $client->getMaxConnections());
		$client->setMaxConnections('3');
		self::assertSame(3, $client->getMaxConnections());
		$client->setMaxConnections(-1);
		self::assertSame(0, $client->getMaxConnections());
		self::assertSame(0, $client->getPendingCount());
		$this->expectException(TInvalidDataValueException::class);
		$client->setPollInterval(0);
	}

	public function testDownloadReturnsBodyAndStatus(): void
	{
		$this->requireRunningHttpServer();
		$client = new TCurlMultiHttpClient();
		$response = $client->download('GET', self::url('/'));
		self::assertSame(200, $response->getStatusCode());
		self::assertSame('hello', $response->getBody());
		self::assertSame(0, $client->getPendingCount());
	}

	public function testDownloadAsyncResolvesWithTheResponse(): void
	{
		$this->requireRunningHttpServer();
		$client = new TCurlMultiHttpClient();
		$future = $client->downloadAsync('POST', self::url('/echo'), ['Content-Type' => 'application/json'], '{"name":"Alice"}');
		self::assertInstanceOf(TFuture::class, $future);
		self::assertSame(1, $client->getPendingCount());
		$body = $future->await()->getJson();
		self::assertSame('POST', $body['method']);
		self::assertSame('{"name":"Alice"}', $body['body']);
		self::assertSame('application/json', $body['headers']['Content-Type'] ?? null);
		self::assertSame(0, $client->getPendingCount());
	}

	public function testResponseHeadersAndNon2xxStatus(): void
	{
		$this->requireRunningHttpServer();
		$client = new TCurlMultiHttpClient();
		$headers = $client->downloadAsync('GET', self::url('/headers'));
		$status = $client->downloadAsync('GET', self::url('/status/500'));
		[$headers, $status] = TAsync::all([$headers, $status])->await();
		self::assertSame('alpha', $headers->getHeader('X-Custom-A'));
		self::assertSame('bravo', $headers->getHeader('X-Custom-B'));
		self::assertSame(500, $status->getStatusCode());
		self::assertSame('status 500', $status->getBody());
	}

	public function testTransfersSharePumpAndThePumpStopsWhenIdle(): void
	{
		$this->requireRunningHttpServer();
		$client = new TCurlMultiHttpClient();
		$loop = TAsync::getLoop();
		$before = count($loop->getIdentifiers());
		$a = $client->downloadAsync('GET', self::url('/slow?delay=0.1'));
		$b = $client->downloadAsync('GET', self::url('/echo'));
		self::assertSame(2, $client->getPendingCount(), 'Both transfers are in flight at once');
		self::assertCount($before + 1, $loop->getIdentifiers(), 'One pump timer serves every transfer');
		[$a, $b] = TAsync::all([$a, $b])->await();
		self::assertSame('slow', $a->getBody());
		self::assertSame('GET', $b->getJson()['method']);
		self::assertSame(0, $client->getPendingCount());
		self::assertCount($before, $loop->getIdentifiers(), 'The pump timer is cancelled once no transfer is pending');
	}

	public function testDownloadYieldsToOtherFibers(): void
	{
		$this->requireRunningHttpServer();
		$client = new TCurlMultiHttpClient();
		$order = [];
		$download = TAsync::run(function () use ($client, &$order): string {
			$order[] = 'request';
			$body = $client->download('GET', self::url('/slow?delay=0.2'))->getBody();
			$order[] = 'response';
			return $body;
		});
		$other = TAsync::run(function () use (&$order): void {
			TAsync::sleep(0.02);
			$order[] = 'other';
		});
		self::assertSame(['request'], $order, 'The download fiber yields at the wait');
		self::assertSame('slow', $download->await());
		$other->await();
		self::assertSame(['request', 'other', 'response'], $order);
	}

	public function testTransportErrorRejects(): void
	{
		$client = new TCurlMultiHttpClient();
		$client->setTimeout(2);
		$future = $client->downloadAsync('GET', 'http://127.0.0.1:1/');
		try {
			$future->await();
			self::fail('A refused connection rejects the future');
		} catch (THttpClientException $e) {
			self::assertStringContainsString('transport error', $e->getMessage());
		}
		self::assertSame(0, $client->getPendingCount());
		$this->expectException(THttpClientException::class);
		$client->download('GET', 'http://127.0.0.1:1/');
	}

	public function testPrepareForForkReleasesAnIdleMultiHandle(): void
	{
		$this->requireRunningHttpServer();
		$app = Prado::getApplication();
		$client = new TCurlMultiHttpClient();
		$client->download('GET', self::url('/'));
		self::assertNotNull(PradoUnit::getProp($client, '_multi'));

		$app->raiseEvent(TProcessHelper::FX_PREPARE_FOR_FORK, $app, null);
		self::assertNull(PradoUnit::getProp($client, '_multi'), 'An idle client releases its multi handle before a fork');
		self::assertSame('hello', $client->download('GET', self::url('/'))->getBody(), 'The next transfer creates a new handle');

		$future = $client->downloadAsync('GET', self::url('/slow?delay=0.1'));
		$app->raiseEvent(TProcessHelper::FX_PREPARE_FOR_FORK, $app, null);
		self::assertNotNull(PradoUnit::getProp($client, '_multi'), 'A pending transfer keeps the handle');
		self::assertSame('slow', $future->await()->getBody());
	}

	public function testAForkedChildExitsAfterTheClientWasUsed(): void
	{
		$this->requireRunningHttpServer();
		if (!TProcessHelper::isForkable()) {
			self::markTestSkipped('pcntl forking is not available.');
		}
		$client = new TCurlMultiHttpClient();
		$client->download('GET', self::url('/'));
		$fork = TProcessFork::fork(fn () => 0);
		$exit = TAsync::timeout(TAsync::run(fn () => $fork->wait()), 10.0);
		try {
			self::assertSame(0, $exit->await(), 'The child exits instead of waiting on libcurl threads it does not have');
		} catch (TCancelledException $e) {
			$fork->kill();
			self::fail('The forked child did not exit within 10 seconds.');
		}
	}

	public function testCancellationRemovesTheTransfer(): void
	{
		$this->requireRunningHttpServer();
		$client = new TCurlMultiHttpClient();
		$loop = TAsync::getLoop();
		$before = count($loop->getIdentifiers());
		$start = microtime(true);
		$future = $client->downloadAsync('GET', self::url('/slow?delay=1'), [], null, new TCancellationToken(0.05));
		try {
			$future->await();
			self::fail('The cancellation rejects the future');
		} catch (TCancelledException $e) {
		}
		self::assertLessThan(0.8, microtime(true) - $start);
		self::assertSame(0, $client->getPendingCount());
		self::assertCount($before, $loop->getIdentifiers());

		$cancelled = new TCancellationToken();
		$cancelled->cancel();
		$future = $client->downloadAsync('GET', self::url('/'), [], null, $cancelled);
		self::assertTrue($future->isRejected(), 'A cancelled token rejects before the transfer starts');
		self::assertSame(0, $client->getPendingCount());
	}
}
