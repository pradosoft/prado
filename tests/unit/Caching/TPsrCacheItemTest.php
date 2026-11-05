<?php

namespace Prado\Test\Unit\Caching;

use Prado\Caching\TPsrCacheItem;
use Prado\Util\Clock\IClock;
use Prado\Util\Clock\TMockClock;
use Psr\Cache\CacheItemInterface;

/**
 * Tests TPsrCacheItem: key, value, hit state, and the expiration set absolutely or
 * relative to the item's clock.
 */
class TPsrCacheItemTest extends \PHPUnit\Framework\TestCase
{
	private const NOW = 1700000000;

	private function newItem(string $key = 'key', mixed $value = null, bool $isHit = false): TPsrCacheItem
	{
		$item = new TPsrCacheItem($key, $value, $isHit);
		$item->setClock((new TMockClock())->setNow(self::NOW));
		return $item;
	}

	public function testMiss(): void
	{
		$item = new TPsrCacheItem('key');
		$this->assertInstanceOf(CacheItemInterface::class, $item);
		$this->assertSame('key', $item->getKey());
		$this->assertNull($item->get());
		$this->assertFalse($item->isHit());
		$this->assertNull($item->getExpiration());
	}

	public function testHit(): void
	{
		$item = new TPsrCacheItem('key', false, true);
		$this->assertFalse($item->get());
		$this->assertTrue($item->isHit());
	}

	public function testSet(): void
	{
		$item = new TPsrCacheItem('key');
		$this->assertSame($item, $item->set('value'));
		$this->assertSame('value', $item->get());
		$this->assertFalse($item->isHit());
		$hit = new TPsrCacheItem('key', 'old', true);
		$hit->set('new');
		$this->assertSame('new', $hit->get());
		$this->assertTrue($hit->isHit());
	}

	public function testExpiresAt(): void
	{
		$item = $this->newItem();
		$mutable = new \DateTime('@' . self::NOW);
		$this->assertSame($item, $item->expiresAt($mutable));
		$expiration = $item->getExpiration();
		$this->assertInstanceOf(\DateTimeImmutable::class, $expiration);
		$this->assertSame(self::NOW, $expiration->getTimestamp());
		$mutable->modify('+1 day');
		$this->assertSame(self::NOW, $item->getExpiration()->getTimestamp());
		$this->assertSame($item, $item->expiresAt(null));
		$this->assertNull($item->getExpiration());
	}

	public function testExpiresAfterSeconds(): void
	{
		$item = $this->newItem();
		$this->assertSame($item, $item->expiresAfter(60));
		$this->assertSame(self::NOW + 60, $item->getExpiration()->getTimestamp());
		$item->expiresAfter(-10);
		$this->assertSame(self::NOW - 10, $item->getExpiration()->getTimestamp());
		$item->expiresAfter(0);
		$this->assertSame(self::NOW, $item->getExpiration()->getTimestamp());
		$this->assertSame($item, $item->expiresAfter(null));
		$this->assertNull($item->getExpiration());
	}

	public function testExpiresAfterInterval(): void
	{
		$item = $this->newItem();
		$item->expiresAfter(new \DateInterval('PT1H30M'));
		$this->assertSame(self::NOW + 5400, $item->getExpiration()->getTimestamp());
		$negative = new \DateInterval('PT30S');
		$negative->invert = 1;
		$item->expiresAfter($negative);
		$this->assertSame(self::NOW - 30, $item->getExpiration()->getTimestamp());
	}

	public function testExpiresAfterKeepsMicroseconds(): void
	{
		$item = new TPsrCacheItem('key');
		$item->setClock((new TMockClock())->setNow(new \DateTimeImmutable('@' . self::NOW . '.250000')));
		$item->expiresAfter(1);
		$this->assertSame((self::NOW + 1) . '.250000', $item->getExpiration()->format('U.u'));
	}

	public function testDefaultClock(): void
	{
		$item = new TPsrCacheItem('key');
		$this->assertInstanceOf(IClock::class, $item->getClock());
		$before = time();
		$item->expiresAfter(100);
		$this->assertGreaterThanOrEqual($before + 100, $item->getExpiration()->getTimestamp());
	}
}
