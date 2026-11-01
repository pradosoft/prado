<?php

namespace Prado\Test\Unit\Web\Services\ServerEvents;

use Prado\IO\Async\TAsync;
use Prado\Web\Services\ServerEvents\IServerEventSink;
use Prado\Web\Services\ServerEvents\TServerEvent;
use Prado\Web\Services\ServerEvents\TServerEventMemoryBroker;
use Prado\Web\Services\ServerEvents\TServerEventStreamer;
use Prado\Web\TMediaType;

/**
 * A sink that records the headers, chunks, and close; a disconnect follows a chunk count.
 */
class TServerEventStreamerTestSink implements IServerEventSink
{
	public ?array $headers = null;
	public array $chunks = [];
	public int $closes = 0;
	public ?int $connectedChunks = null;

	public function open(array $headers): void
	{
		$this->headers = $headers;
	}

	public function write(string $chunk): bool
	{
		$this->chunks[] = $chunk;
		return $this->connectedChunks === null || count($this->chunks) < $this->connectedChunks;
	}

	public function close(): void
	{
		$this->closes++;
	}
}

/**
 * A broker whose wait throws, to show the sink still closes.
 */
class TServerEventStreamerTestFailingBroker extends TServerEventMemoryBroker
{
	public function receive(string $channel, string $lastEventId, float $timeout): array
	{
		throw new \RuntimeException('broker down');
	}
}

class TServerEventStreamerTest extends \PHPUnit\Framework\TestCase
{
	protected function tearDown(): void
	{
		TAsync::setLoop(null);
	}

	/**
	 * A streamer with a pinned clock and a cache broker that sleeps on that clock.
	 */
	private function streamer(): array
	{
		$clock = new TServerEventMockClock();
		$broker = new TServerEventTestCacheBroker(new TServerEventArrayCache());
		$broker->setClock($clock);
		$broker->setMonotonicClock($clock->elapsed);
		$broker->setPollInterval(1.0);
		$streamer = new TServerEventStreamer();
		$streamer->setMonotonicClock($clock->elapsed);
		return [$streamer, $broker, $clock];
	}

	public function testHeadersCarryNoConnectionSpecificField(): void
	{
		$headers = TServerEventStreamer::getHeaders();
		self::assertSame([
			'Content-Type' => TMediaType::EVENT_STREAM . '; charset=UTF-8',
			'Cache-Control' => 'no-cache',
			'X-Accel-Buffering' => 'no',
		], $headers);
		foreach (['connection', 'keep-alive', 'proxy-connection', 'transfer-encoding', 'upgrade'] as $name) {
			self::assertArrayNotHasKey($name, array_change_key_case($headers), "RFC 9113 §8.2.2 forbids $name.");
		}
	}

	public function testRunOpensWritesAndClosesTheSink(): void
	{
		[$streamer, $broker, $clock] = $this->streamer();
		$streamer->setMaxLifetime(6);
		$streamer->setHeartbeatInterval(4);
		$streamer->setRetryInterval(500);
		$first = $broker->publish('orders', 'changed', 1);
		$second = $broker->publish('orders', 'changed', 2);
		$sink = new TServerEventStreamerTestSink();

		$lastId = $streamer->run($sink, $broker, 'orders', '');

		self::assertSame(TServerEventStreamer::getHeaders(), $sink->headers);
		self::assertSame([
			"retry: 500\n\n",
			TServerEventStreamer::formatEvent($first) . TServerEventStreamer::formatEvent($second),
			": heartbeat\n\n",
		], $sink->chunks);
		self::assertSame(1, $sink->closes);
		self::assertSame($second->getID(), $lastId);
	}

	/**
	 * A wall-clock step during a stream, back or forward an hour, keeps MaxLifetime and the heartbeats.
	 * @dataProvider wallClockStepProvider
	 */
	public function testWallClockStepKeepsTheLifetimeAndHeartbeats(float $step): void
	{
		[$streamer, $broker, $clock] = $this->streamer();
		$streamer->setMaxLifetime(10);
		$streamer->setHeartbeatInterval(4);
		$clock->onSleep = function ($clock, int $count) use ($step) {
			if ($count === 2) {
				$clock->setMicrotime($clock->microtime() + $step);
			}
		};
		$sink = new TServerEventStreamerTestSink();

		$streamer->run($sink, $broker, 'orders', '');

		self::assertSame(["retry: 3000\n\n", ": heartbeat\n\n", ": heartbeat\n\n"], $sink->chunks);
		self::assertSame(10.0, $clock->elapsed->seconds(), 'The stream lasted MaxLifetime of elapsed time.');
	}

	public static function wallClockStepProvider(): array
	{
		return ['back an hour' => [-3600.0], 'forward an hour' => [3600.0], 'no step' => [0.0]];
	}

	public function testDisconnectEndsTheStreamAndReturnsTheLastSentId(): void
	{
		[$streamer, $broker] = $this->streamer();
		$streamer->setMaxLifetime(100);
		$event = $broker->publish('orders', 'changed', 1);
		$sink = new TServerEventStreamerTestSink();
		$sink->connectedChunks = 2;

		self::assertSame($event->getID(), $streamer->run($sink, $broker, 'orders', ''));
		self::assertCount(2, $sink->chunks);
		self::assertSame(1, $sink->closes);

		$sink = new TServerEventStreamerTestSink();
		$sink->connectedChunks = 1;
		self::assertSame('resume-id', $streamer->run($sink, $broker, 'orders', 'resume-id'), 'A disconnect at retry: sends no event.');
		self::assertSame(["retry: 3000\n\n"], $sink->chunks);
	}

	public function testSinkClosesWhenTheBrokerThrows(): void
	{
		$streamer = new TServerEventStreamer();
		$sink = new TServerEventStreamerTestSink();
		try {
			$streamer->run($sink, new TServerEventStreamerTestFailingBroker(), 'orders', '');
			self::fail('Expected the broker exception');
		} catch (\RuntimeException $e) {
			self::assertSame('broker down', $e->getMessage());
		}
		self::assertSame(1, $sink->closes);
	}

	public function testOneStreamerServesConcurrentStreamsInFibers(): void
	{
		$broker = new TServerEventMemoryBroker();
		$streamer = new TServerEventStreamer();
		$streamer->setMaxLifetime(0.3);
		$sinks = [new TServerEventStreamerTestSink(), new TServerEventStreamerTestSink()];
		$futures = array_map(fn ($sink) => TAsync::run(fn () => $streamer->run($sink, $broker, 'orders', '')), $sinks);
		TAsync::getLoop()->delay(0.05, fn () => $broker->publish('orders', 'changed', 'both'));

		$ids = array_map(fn ($future) => $future->await(), $futures);

		self::assertSame($ids[0], $ids[1]);
		foreach ($sinks as $sink) {
			self::assertCount(2, $sink->chunks);
			self::assertStringEndsWith("data: {\"event\":\"changed\",\"data\":\"both\"}\n\n", $sink->chunks[1]);
			self::assertSame(1, $sink->closes);
		}
	}

	public function testResolveLastEventIdPrecedence(): void
	{
		$broker = new TServerEventMemoryBroker();
		$latest = $broker->publish('orders', 'changed', 1)->getID();
		self::assertSame('h', TServerEventStreamer::resolveLastEventId($broker, 'orders', 'h', 'p'));
		self::assertSame('p', TServerEventStreamer::resolveLastEventId($broker, 'orders', null, 'p'));
		self::assertSame('', TServerEventStreamer::resolveLastEventId($broker, 'orders', null, ''));
		self::assertSame($latest, TServerEventStreamer::resolveLastEventId($broker, 'orders'));
	}

	public function testFormatEvent(): void
	{
		$event = new TServerEvent('ab12-3', 'c', 'note', "line1\nline2");
		self::assertSame("id: ab12-3\ndata: {\"event\":\"note\",\"data\":\"line1\\nline2\"}\n\n", TServerEventStreamer::formatEvent($event));
	}
}
