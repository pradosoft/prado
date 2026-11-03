<?php

namespace Prado\Test\Unit\Web\Services\ServerEvents;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IO\Async\TAsync;
use Prado\TComponent;
use Prado\Web\Services\ServerEvents\IServerEventBroker;
use Prado\Web\Services\ServerEvents\TServerEvent;
use Prado\Web\Services\ServerEvents\TServerEventBroker;
use Prado\Web\Services\ServerEvents\TServerEventCacheBroker;
use Prado\Web\Services\ServerEvents\TServerEventMemoryBroker;

/**
 * A global listener of fxServerEventPublished, as a relay to another transport is.
 */
class TServerEventBrokerTestRelay extends TComponent
{
	public array $published = [];

	public function fxServerEventPublished($sender, $param)
	{
		$this->published[] = [$sender, $param];
	}
}

class TServerEventBrokerTest extends \PHPUnit\Framework\TestCase
{
	protected function tearDown(): void
	{
		TAsync::setLoop(null);
	}

	private function memoryBroker(?TServerEventMockClock $clock = null): TServerEventMemoryBroker
	{
		$broker = new TServerEventMemoryBroker();
		$broker->setClock($clock ?? new TServerEventMockClock());
		return $broker;
	}

	private function cacheBroker(?TServerEventArrayCache $cache = null, ?TServerEventMockClock $clock = null): TServerEventTestCacheBroker
	{
		$clock ??= new TServerEventMockClock();
		$broker = new TServerEventTestCacheBroker($cache ?? new TServerEventArrayCache());
		$broker->setClock($clock);
		$broker->setMonotonicClock($clock->elapsed);
		return $broker;
	}

	public function testIsValidName(): void
	{
		self::assertTrue(TServerEventBroker::isValidName('orders'));
		self::assertTrue(TServerEventBroker::isValidName('user:42.inbox_new-1'));
		self::assertTrue(TServerEventBroker::isValidName(str_repeat('a', 128)));
		self::assertFalse(TServerEventBroker::isValidName(''));
		self::assertFalse(TServerEventBroker::isValidName(str_repeat('a', 129)));
		self::assertFalse(TServerEventBroker::isValidName('a b'));
		self::assertFalse(TServerEventBroker::isValidName("a\nb"));
		self::assertFalse(TServerEventBroker::isValidName('a/b'));
	}

	public function testPublishAnnouncesTheStoredEventGlobally(): void
	{
		$relay = new TServerEventBrokerTestRelay();
		$relay->listen();
		try {
			$memory = $this->memoryBroker();
			$cache = $this->cacheBroker();
			$first = $memory->publish('orders', 'changed', ['id' => 1]);
			$second = $cache->publish('inbox', 'new', 2);

			self::assertCount(2, $relay->published);
			self::assertSame($memory, $relay->published[0][0]);
			self::assertSame($first, $relay->published[0][1]);
			self::assertSame($cache, $relay->published[1][0]);
			self::assertSame($second, $relay->published[1][1]);
			self::assertSame($second->getID(), $cache->getLastEventId('inbox'), 'The event was stored before the announcement.');

			try {
				$memory->publish('bad channel', 'e');
			} catch (TInvalidDataValueException $e) {
			}
			self::assertCount(2, $relay->published, 'A rejected publish announces nothing.');

			$direct = new TServerEvent('x-1', 'orders', 'e', null);
			TServerEventBroker::announcePublished($memory, $direct);
			self::assertSame([$memory, $direct], $relay->published[2], 'A broker that implements the interface itself announces through the helper.');
			self::assertSame('fxServerEventPublished', TServerEventBroker::PUBLISHED_EVENT);
		} finally {
			$relay->unlisten();
		}
	}

	public function testBrokersImplementTheInterface(): void
	{
		self::assertInstanceOf(IServerEventBroker::class, new TServerEventMemoryBroker());
		self::assertInstanceOf(IServerEventBroker::class, new TServerEventCacheBroker());
	}

	public function testPublishNumbersEventsInOrder(): void
	{
		$clock = new TServerEventMockClock(1700000000.5);
		$broker = $this->memoryBroker($clock);
		self::assertSame('', $broker->getLastEventId('orders'));

		$first = $broker->publish('orders', 'changed', ['id' => 1]);
		$second = $broker->publish('orders', 'removed', ['id' => 2]);

		self::assertInstanceOf(TServerEvent::class, $first);
		self::assertMatchesRegularExpression('/^[0-9a-f]{8}-1$/', $first->getID());
		[$generation] = explode('-', $first->getID());
		self::assertSame($generation . '-2', $second->getID());
		self::assertSame('orders', $first->getChannel());
		self::assertSame('changed', $first->getName());
		self::assertSame(['id' => 1], $first->getData());
		self::assertSame(1700000000.5, $first->getTime());
		self::assertSame($second->getID(), $broker->getLastEventId('orders'));
		self::assertSame('', $broker->getLastEventId('other'));
	}

	public function testPublishRejectsInvalidNamesAndData(): void
	{
		$broker = $this->memoryBroker();
		foreach ([['bad channel', 'e', null], ['c', 'bad event', null], ['c', 'e', "\xB1\x31"]] as [$channel, $event, $data]) {
			try {
				$broker->publish($channel, $event, $data);
				self::fail('Expected TInvalidDataValueException');
			} catch (TInvalidDataValueException $e) {
				self::assertStringContainsString('servereventbroker_', $e->getErrorCode());
			}
		}
		self::assertSame('', $broker->getLastEventId('c'));
	}

	public function testReceiveReturnsTheEventsAfterAnId(): void
	{
		foreach ([$this->memoryBroker(), $this->cacheBroker()] as $broker) {
			$a = $broker->publish('c', 'e', 'a');
			$b = $broker->publish('c', 'e', 'b');
			$c = $broker->publish('c', 'e', 'c');
			$ids = fn (array $events) => array_map(fn (TServerEvent $event) => $event->getID(), $events);

			self::assertSame([$a->getID(), $b->getID(), $c->getID()], $ids($broker->receive('c', '', 0.0)));
			self::assertSame([$b->getID(), $c->getID()], $ids($broker->receive('c', $a->getID(), 0.0)));
			self::assertSame([], $broker->receive('c', $c->getID(), 0.0));
			self::assertSame(['b', 'c'], array_map(fn ($event) => $event->getData(), $broker->receive('c', $a->getID(), 0.0)));
			// Another generation, as after a cache flush, and malformed IDs select every event.
			self::assertCount(3, $broker->receive('c', 'ffffffff-2', 0.0));
			self::assertCount(3, $broker->receive('c', 'garbage', 0.0));
			self::assertCount(3, $broker->receive('c', explode('-', $a->getID())[0] . '-x', 0.0));
			self::assertSame([], $broker->receive('none', '', 0.0));
		}
	}

	public function testBufferSizeKeepsTheNewestEvents(): void
	{
		$broker = $this->memoryBroker();
		$broker->setBufferSize(2);
		$broker->publish('c', 'e', 1);
		$broker->publish('c', 'e', 2);
		$broker->publish('c', 'e', 3);
		self::assertSame([2, 3], array_map(fn ($event) => $event->getData(), $broker->receive('c', '', 0.0)));
	}

	public function testEventLifetimeDropsExpiredEvents(): void
	{
		$clock = new TServerEventMockClock();
		$broker = $this->memoryBroker($clock);
		$broker->setEventLifetime(60);
		$broker->publish('c', 'e', 'old');
		$clock->sleep(30);
		$last = $broker->publish('c', 'e', 'new');
		$clock->sleep(31);
		self::assertSame(['new'], array_map(fn ($event) => $event->getData(), $broker->receive('c', '', 0.0)));
		self::assertSame($last->getID(), $broker->getLastEventId('c'));
		$clock->sleep(30);
		self::assertSame([], $broker->receive('c', '', 0.0));
		self::assertSame('', $broker->getLastEventId('c'));
	}

	public function testPropertyValidation(): void
	{
		$broker = new TServerEventMemoryBroker();
		self::assertSame(TServerEventBroker::DEFAULT_BUFFER_SIZE, $broker->getBufferSize());
		self::assertSame(TServerEventBroker::DEFAULT_EVENT_LIFETIME, $broker->getEventLifetime());
		$broker->setBufferSize('5');
		$broker->setEventLifetime('7');
		self::assertSame(5, $broker->getBufferSize());
		self::assertSame(7, $broker->getEventLifetime());
		foreach (['setBufferSize', 'setEventLifetime'] as $setter) {
			try {
				$broker->$setter(0);
				self::fail("Expected $setter(0) to throw");
			} catch (TInvalidDataValueException $e) {
				self::assertStringEndsWith('_invalid', $e->getErrorCode());
			}
		}

		$cacheBroker = new TServerEventCacheBroker();
		self::assertSame(TServerEventCacheBroker::DEFAULT_POLL_INTERVAL, $cacheBroker->getPollInterval());
		$cacheBroker->setPollInterval('0.25');
		self::assertSame(0.25, $cacheBroker->getPollInterval());
		$this->expectException(TInvalidDataValueException::class);
		$cacheBroker->setPollInterval(0);
	}

	public function testCacheBrokerSharesEventsThroughTheCache(): void
	{
		$cache = new TServerEventArrayCache();
		$publisher = $this->cacheBroker($cache);
		$publisher->setEventLifetime(120);
		$stream = $this->cacheBroker($cache);

		$event = $publisher->publish('orders', 'changed', ['id' => 9]);

		self::assertSame($event->getID(), $stream->getLastEventId('orders'));
		$received = $stream->receive('orders', '', 0.0);
		self::assertCount(1, $received);
		self::assertSame(['id' => 9], $received[0]->getData());
		self::assertSame(120, $cache->expires[TServerEventCacheBroker::CACHE_KEY_PREFIX . 'orders']);
		self::assertSame([TServerEventCacheBroker::CACHE_KEY_PREFIX . 'orders'], array_keys($cache->values), 'The publish released its lock.');
	}

	public function testCacheBrokerPollsUntilTheTimeout(): void
	{
		$clock = new TServerEventMockClock();
		$broker = $this->cacheBroker(null, $clock);
		$broker->setPollInterval(1.0);
		$start = $clock->microtime();

		self::assertSame([], $broker->receive('c', '', 2.5));

		self::assertSame([1.0, 1.0, 0.5], $clock->sleeps);
		self::assertEqualsWithDelta($start + 2.5, $clock->microtime(), 0.0001);
		self::assertSame(4, $broker->cache->gets);
	}

	public function testCacheBrokerTimeoutIgnoresAWallClockStep(): void
	{
		foreach ([-3600.0, 3600.0] as $step) {
			$clock = new TServerEventMockClock();
			$broker = $this->cacheBroker(null, $clock);
			$broker->setPollInterval(1.0);
			$clock->onSleep = function ($clock, int $count) use ($step) {
				if ($count === 1) {
					$clock->setMicrotime($clock->microtime() + $step);
				}
			};

			self::assertSame([], $broker->receive('c', '', 2.5));

			self::assertSame([1.0, 1.0, 0.5], $clock->sleeps, "A step of {$step} s keeps the 2.5 s timeout.");
		}
	}

	public function testCacheBrokerReturnsAnEventPublishedWhilePolling(): void
	{
		$cache = new TServerEventArrayCache();
		$clock = new TServerEventMockClock();
		$publisher = $this->cacheBroker($cache, $clock);
		$stream = $this->cacheBroker($cache, $clock);
		$stream->setPollInterval(0.5);
		$clock->onSleep = function ($clock, int $count) use ($publisher) {
			if ($count === 2) {
				$publisher->publish('c', 'ready', 'go');
			}
		};

		$events = $stream->receive('c', '', 10.0);

		self::assertCount(1, $events);
		self::assertSame('ready', $events[0]->getName());
		self::assertSame([0.5, 0.5], $clock->sleeps);
	}

	public function testCacheBrokerRequiresACacheModule(): void
	{
		$broker = new TServerEventCacheBroker();
		$broker->setCacheModuleID('no-such-cache-module');
		$this->expectException(TConfigurationException::class);
		$broker->getLastEventId('c');
	}

	public function testMemoryBrokerWakesAWaitingFiber(): void
	{
		$broker = new TServerEventMemoryBroker();
		$future = TAsync::run(fn () => $broker->receive('c', '', 5.0));
		self::assertFalse($future->isComplete());
		self::assertSame(1, $broker->getWaiterCount('c'));

		$broker->publish('c', 'e', 'hello');
		$started = hrtime(true);
		$events = $future->await();

		self::assertLessThan(1.0, (hrtime(true) - $started) / 1e9);
		self::assertCount(1, $events);
		self::assertSame('hello', $events[0]->getData());
		self::assertSame(0, $broker->getWaiterCount('c'));
	}

	public function testMemoryBrokerReturnsNothingAfterTheTimeout(): void
	{
		$broker = new TServerEventMemoryBroker();
		$broker->publish('other', 'e', 1);
		self::assertSame([], $broker->receive('c', '', 0.05));
		self::assertSame(0, $broker->getWaiterCount('c'));
	}
}
