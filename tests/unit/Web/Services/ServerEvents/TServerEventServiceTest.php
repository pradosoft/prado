<?php

namespace Prado\Test\Unit\Web\Services\ServerEvents;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Async\TAsync;
use Prado\IO\Async\TFiberContext;
use Prado\Prado;
use Prado\Security\TUser;
use Prado\Security\TUserManager;
use Prado\TApplication;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\Services\ServerEvents\IServerEventSink;
use Prado\Web\Services\ServerEvents\TServerEventCacheBroker;
use Prado\Web\Services\ServerEvents\TServerEventMemoryBroker;
use Prado\Web\Services\ServerEvents\TServerEventResponseSink;
use Prado\Web\Services\ServerEvents\TServerEventService;
use Prado\Web\Services\ServerEvents\TServerEventStreamer;
use Prado\Web\TMediaType;
use Prado\Xml\TXmlDocument;

/**
 * A request with a service parameter, query items, and headers.
 */
class TServerEventServiceTestRequest
{
	public string $serviceParameter = '';
	public array $items = [];
	public array $headers = [];

	public function getServiceParameter()
	{
		return $this->serviceParameter;
	}

	public function itemAt($key)
	{
		return $this->items[$key] ?? null;
	}

	public function contains($key)
	{
		return array_key_exists($key, $this->items);
	}

	public function getHeader($name, $default = null)
	{
		return $this->headers[strtolower($name)] ?? $default;
	}
}

/**
 * A response that records the status, headers, and written output.
 */
class TServerEventServiceTestResponse
{
	public int $statusCode = 200;
	public array $headers = [];
	public ?string $contentType = null;
	public ?string $charset = null;
	public string $output = '';
	public int $flushes = 0;

	public function setStatusCode($status, $reason = null)
	{
		$this->statusCode = $status;
	}

	public function appendHeader($header, bool $replace = true, int $response_code = 0): void
	{
		$this->headers[] = $header;
	}

	public function setContentType($type)
	{
		$this->contentType = $type;
	}

	public function setCharset($charset)
	{
		$this->charset = $charset;
	}

	public function write($str)
	{
		$this->output .= $str;
	}

	public function flush($continueBuffering = true)
	{
		$this->flushes++;
	}
}

/**
 * The response sink with the SAPI output calls recorded; a disconnect follows a chunk count.
 */
class TServerEventServiceTestSink extends TServerEventResponseSink
{
	public array $calls = [];
	public ?int $connectedChunks = null;
	public int $chunks = 0;
	public int $closes = 0;

	public function write(string $chunk): bool
	{
		$this->chunks++;
		return parent::write($chunk);
	}

	public function close(): void
	{
		$this->closes++;
		parent::close();
	}

	public function callApplyContentType(string $value): void
	{
		$this->applyContentType($value);
	}

	protected function openOutput(): void
	{
		$this->calls[] = 'openOutput';
	}

	protected function isClientConnected(): bool
	{
		return $this->connectedChunks === null || $this->chunks < $this->connectedChunks;
	}
}

/**
 * The service over fake request and response objects; the session seam and the sink record their calls.
 */
class TServerEventServiceTestService extends TServerEventService
{
	public TServerEventServiceTestRequest $fakeRequest;
	public TServerEventServiceTestResponse $fakeResponse;
	public ?object $broker = null;
	public array $calls = [];
	public ?int $connectedChunks = null;
	public ?TServerEventServiceTestSink $sink = null;

	public function __construct()
	{
		parent::__construct();
		$this->fakeRequest = new TServerEventServiceTestRequest();
		$this->fakeResponse = new TServerEventServiceTestResponse();
	}

	public function getRequest()
	{
		return $this->fakeRequest;
	}

	public function getResponse()
	{
		return $this->fakeResponse;
	}

	protected function resolveBroker(): \Prado\Web\Services\ServerEvents\IServerEventBroker
	{
		return $this->broker ?? parent::resolveBroker();
	}

	protected function releaseSession(): void
	{
		$this->calls[] = 'releaseSession';
	}

	protected function createSink(): IServerEventSink
	{
		$this->sink = new TServerEventServiceTestSink($this->getResponse(), parent::createSink()->getTimeLimit());
		$this->sink->calls = &$this->calls;
		$this->sink->connectedChunks = $this->connectedChunks;
		return $this->sink;
	}

	public function callResolveBroker(): \Prado\Web\Services\ServerEvents\IServerEventBroker
	{
		return parent::resolveBroker();
	}
}

class TServerEventServiceTest extends \PHPUnit\Framework\TestCase
{
	protected function tearDown(): void
	{
		TAsync::setLoop(null);
	}

	/**
	 * A service on channel `orders` with a valid token over a cache broker and a pinned clock.
	 */
	private function service(?TServerEventMockClock $clock = null): array
	{
		$clock ??= new TServerEventMockClock();
		$broker = new TServerEventTestCacheBroker(new TServerEventArrayCache());
		$broker->setClock($clock);
		$broker->setMonotonicClock($clock->elapsed);
		$broker->setPollInterval(1.0);
		$service = new TServerEventServiceTestService();
		$service->getStreamer()->setMonotonicClock($clock->elapsed);
		$service->broker = $broker;
		$service->fakeRequest->serviceParameter = 'orders';
		$service->fakeRequest->items[TServerEventService::TOKEN_PARAMETER] = TServerEventService::createChannelToken('orders');
		return [$service, $broker, $clock];
	}

	private function frames(string $output): array
	{
		return array_values(array_filter(explode("\n\n", $output), fn ($frame) => $frame !== ''));
	}

	public function testInvalidChannelIsNotFound(): void
	{
		[$service] = $this->service();
		$service->fakeRequest->serviceParameter = 'bad channel';
		$service->run();
		self::assertSame(404, $service->fakeResponse->statusCode);
		self::assertSame('', $service->fakeResponse->output);
		self::assertSame([], $service->calls);
	}

	public function testMissingOrWrongTokenIsForbidden(): void
	{
		foreach ([null, '', 'abc', TServerEventService::createChannelToken('other')] as $token) {
			[$service] = $this->service();
			if ($token === null) {
				unset($service->fakeRequest->items[TServerEventService::TOKEN_PARAMETER]);
			} else {
				$service->fakeRequest->items[TServerEventService::TOKEN_PARAMETER] = $token;
			}
			$service->run();
			self::assertSame(403, $service->fakeResponse->statusCode);
			self::assertSame('', $service->fakeResponse->output);
			self::assertSame([], $service->calls);
		}
	}

	public function testTokenBindsTheChannelToTheUser(): void
	{
		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<users />');
		$manager = new TUserManager();
		$manager->init($config);
		$alice = new TUser($manager);
		$alice->setName('alice');
		$bob = new TUser($manager);
		$bob->setName('bob');
		$tokenFor = fn (TUser $user) => TAsync::run(function () use ($user) {
			TFiberContext::set(TApplication::USER_CONTEXT_KEY, $user);
			return TServerEventService::createChannelToken('orders');
		})->await();

		self::assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $tokenFor($alice));
		self::assertSame($tokenFor($alice), $tokenFor($alice));
		self::assertNotSame($tokenFor($alice), $tokenFor($bob));
		self::assertNotSame($tokenFor($alice), TServerEventService::createChannelToken('orders'));
	}

	public function testConstructChannelUrlCarriesTheChannelAndToken(): void
	{
		$url = TServerEventService::constructChannelUrl('orders', 'events');
		self::assertStringContainsString('events=orders', $url);
		self::assertStringContainsString('token=' . TServerEventService::createChannelToken('orders'), $url);
		self::assertStringNotContainsString('&amp;', $url);
	}

	public function testStreamSendsHeadersRetryEventsAndHeartbeats(): void
	{
		[$service, $broker, $clock] = $this->service();
		$service->setMaxLifetime(10);
		$service->setHeartbeatInterval(4);
		$service->setRetryInterval(1500);
		$first = $broker->publish('orders', 'changed', ['id' => 1, 'path' => 'a/b']);
		$second = $broker->publish('orders', 'removed', 'é');
		$service->fakeRequest->items[TServerEventService::LAST_EVENT_ID_PARAMETER] = '';
		$start = $clock->microtime();

		$service->run();

		$response = $service->fakeResponse;
		self::assertSame(200, $response->statusCode);
		self::assertSame(TMediaType::EVENT_STREAM, $response->contentType);
		self::assertSame('UTF-8', $response->charset);
		self::assertContains('Cache-Control: no-cache', $response->headers);
		self::assertContains('X-Accel-Buffering: no', $response->headers);
		self::assertSame(['releaseSession', 'openOutput'], $service->calls);
		self::assertSame([
			'retry: 1500',
			'id: ' . $first->getID() . "\n" . 'data: {"event":"changed","data":{"id":1,"path":"a/b"}}',
			'id: ' . $second->getID() . "\n" . 'data: {"event":"removed","data":"é"}',
			': heartbeat',
			': heartbeat',
		], $this->frames($response->output));
		self::assertSame(4, $response->flushes, 'Each chunk is flushed.');
		self::assertSame(1, $service->sink->closes);
		self::assertSame(10 + TServerEventService::TIME_LIMIT_MARGIN, $service->sink->getTimeLimit());
		self::assertEqualsWithDelta($start + 10, $clock->microtime(), 0.0001);
	}

	public function testStreamSendsEventsPublishedWhileOpen(): void
	{
		[$service, $broker, $clock] = $this->service();
		$service->setMaxLifetime(5);
		$service->setHeartbeatInterval(60);
		$clock->onSleep = function ($clock, int $count) use ($broker) {
			if ($count === 2) {
				$broker->publish('orders', 'changed', 7);
			}
		};

		$service->run();

		$frames = $this->frames($service->fakeResponse->output);
		self::assertCount(2, $frames);
		self::assertStringEndsWith('data: {"event":"changed","data":7}', $frames[1]);
	}

	public function testResumePointPrefersTheHeaderThenTheQuery(): void
	{
		$sent = function (callable $setup) {
			[$service, $broker] = $this->service();
			$service->setMaxLifetime(1);
			$ids = [];
			foreach (['a', 'b', 'c'] as $data) {
				$ids[] = $broker->publish('orders', 'e', $data)->getID();
			}
			$setup($service, $ids);
			$service->run();
			preg_match_all('/"data":"(\w)"/', $service->fakeResponse->output, $matches);
			return implode('', $matches[1]);
		};

		self::assertSame('', $sent(fn ($service, $ids) => null), 'No resume point starts after the latest event.');
		self::assertSame('bc', $sent(function ($service, $ids) {
			$service->fakeRequest->items[TServerEventService::LAST_EVENT_ID_PARAMETER] = $ids[0];
		}));
		self::assertSame('c', $sent(function ($service, $ids) {
			$service->fakeRequest->items[TServerEventService::LAST_EVENT_ID_PARAMETER] = $ids[0];
			$service->fakeRequest->headers['last-event-id'] = $ids[1];
		}));
		self::assertSame('abc', $sent(function ($service, $ids) {
			$service->fakeRequest->items[TServerEventService::LAST_EVENT_ID_PARAMETER] = '';
		}));
	}

	public function testStreamEndsWhenTheClientDisconnects(): void
	{
		[$service, $broker, $clock] = $this->service();
		$service->setMaxLifetime(100);
		$service->setHeartbeatInterval(1);
		$service->connectedChunks = 3;
		$start = $clock->microtime();

		$service->run();

		self::assertSame(['retry: 3000', ': heartbeat', ': heartbeat'], $this->frames($service->fakeResponse->output));
		self::assertEqualsWithDelta($start + 2, $clock->microtime(), 0.0001);
	}

	public function testStreamWithTheMemoryBroker(): void
	{
		$broker = new TServerEventMemoryBroker();
		$service = new TServerEventServiceTestService();
		$service->broker = $broker;
		$service->setMaxLifetime(0.3);
		$service->fakeRequest->serviceParameter = 'orders';
		$service->fakeRequest->items[TServerEventService::TOKEN_PARAMETER] = TServerEventService::createChannelToken('orders');
		TAsync::getLoop()->delay(0.05, fn () => $broker->publish('orders', 'changed', 'now'));

		$service->run();

		$frames = $this->frames($service->fakeResponse->output);
		self::assertCount(2, $frames);
		self::assertStringEndsWith('data: {"event":"changed","data":"now"}', $frames[1]);
	}

	public function testVerifyChannelToken(): void
	{
		$token = TServerEventService::createChannelToken('orders');
		self::assertTrue(TServerEventService::verifyChannelToken('orders', $token));
		self::assertFalse(TServerEventService::verifyChannelToken('orders', ''));
		self::assertFalse(TServerEventService::verifyChannelToken('orders', strtoupper($token)));
		self::assertFalse(TServerEventService::verifyChannelToken('other', $token));
	}

	public function testResponseSinkSetsTheHeadersOnTheResponse(): void
	{
		$response = new TServerEventServiceTestResponse();
		$sink = new TServerEventServiceTestSink($response, -5);
		self::assertSame(0, $sink->getTimeLimit(), 'A negative time limit leaves the limit unchanged.');

		$sink->open(TServerEventStreamer::getHeaders());

		self::assertSame(TMediaType::EVENT_STREAM, $response->contentType);
		self::assertSame('UTF-8', $response->charset);
		self::assertSame(['Cache-Control: no-cache', 'X-Accel-Buffering: no'], $response->headers);
		self::assertSame(['openOutput'], $sink->calls);

		$sink->callApplyContentType('text/plain;Charset="ISO-8859-1"');
		self::assertSame('text/plain', $response->contentType);
		self::assertSame('ISO-8859-1', $response->charset);
	}

	public function testStreamerIsSharedAndCreatedOnce(): void
	{
		$service = new TServerEventService();
		$streamer = $service->getStreamer();
		self::assertInstanceOf(TServerEventStreamer::class, $streamer);
		self::assertSame($streamer, $service->getStreamer());
		$service->setMaxLifetime(12);
		self::assertSame(12.0, $streamer->getMaxLifetime());
	}

	public function testPropertyDefaultsAndValidation(): void
	{
		$service = new TServerEventService();
		self::assertSame(TServerEventStreamer::DEFAULT_MAX_LIFETIME, $service->getMaxLifetime());
		self::assertSame(TServerEventStreamer::DEFAULT_HEARTBEAT_INTERVAL, $service->getHeartbeatInterval());
		self::assertSame(TServerEventStreamer::DEFAULT_RETRY_INTERVAL, $service->getRetryInterval());
		self::assertSame('', $service->getBrokerID());
		$service->setMaxLifetime('2.5');
		$service->setHeartbeatInterval('1.5');
		$service->setRetryInterval('0');
		$service->setBrokerID('sse');
		self::assertSame(2.5, $service->getMaxLifetime());
		self::assertSame(1.5, $service->getHeartbeatInterval());
		self::assertSame(0, $service->getRetryInterval());
		self::assertSame('sse', $service->getBrokerID());
		foreach ([['setMaxLifetime', 0], ['setHeartbeatInterval', -1], ['setRetryInterval', -1]] as [$setter, $value]) {
			try {
				$service->$setter($value);
				self::fail("Expected $setter($value) to throw");
			} catch (TInvalidDataValueException $e) {
				self::assertStringStartsWith('servereventstreamer_', $e->getErrorCode());
			}
		}
	}

	public function testBrokerResolution(): void
	{
		$app = Prado::getApplication();
		$snapshot = PradoUnit::snapshot($app, ['_modules']);
		try {
			$service = new TServerEventServiceTestService();
			try {
				$service->callResolveBroker();
				self::fail('Expected TConfigurationException without a broker module');
			} catch (TConfigurationException $e) {
				self::assertSame('servereventbroker_required', $e->getErrorCode());
			}

			$first = new TServerEventMemoryBroker();
			$second = new TServerEventCacheBroker();
			$app->setModule('sse-test-first', $first);
			$app->setModule('sse-test-second', $second);
			self::assertSame($first, $service->callResolveBroker());
			$service->setBrokerID('sse-test-second');
			self::assertSame($second, $service->callResolveBroker());

			$service->setBrokerID('no-such-broker');
			try {
				$service->callResolveBroker();
				self::fail('Expected TConfigurationException for an unknown BrokerID');
			} catch (TConfigurationException $e) {
				self::assertSame('servereventbroker_id_invalid', $e->getErrorCode());
			}
		} finally {
			PradoUnit::restore($app, $snapshot);
		}
	}
}
