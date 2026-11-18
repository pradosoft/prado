<?php

namespace Prado\Test\Unit\Caching;

use Prado\Caching\TPsrCacheItem;
use Psr\Cache\CacheItemInterface;

/**
 * Tests TPsrCacheItem: key, value, hit state, and the expiration set absolutely or
 * relative to the current time.
 */
class TPsrCacheItemTest extends \PHPUnit\Framework\TestCase
{
	private const NOW = 1700000000;

	/**
	 * Asserts an expiration lies `$offset` seconds after a time between two microtime readings.
	 * @param float $before the microtime read before the expiration was set
	 * @param float $after the microtime read after the expiration was set
	 * @param float $offset the seconds the expiration lies after the reading
	 * @param TPsrCacheItem $item the item whose expiration is checked
	 */
	private function assertExpiresBetween(float $before, float $after, float $offset, TPsrCacheItem $item): void
	{
		$expiration = (float) $item->getExpiration()->format('U.u');
		$this->assertGreaterThanOrEqual($before + $offset - 0.000001, $expiration);
		$this->assertLessThanOrEqual($after + $offset + 0.000001, $expiration);
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
		$item = new TPsrCacheItem('key');
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
		$item = new TPsrCacheItem('key');
		foreach ([60, -10, 0] as $seconds) {
			$before = microtime(true);
			$this->assertSame($item, $item->expiresAfter($seconds));
			$after = microtime(true);
			$this->assertExpiresBetween($before, $after, $seconds, $item);
		}
		$this->assertSame($item, $item->expiresAfter(null));
		$this->assertNull($item->getExpiration());
	}

	public function testExpiresAfterInterval(): void
	{
		$item = new TPsrCacheItem('key');
		$before = microtime(true);
		$item->expiresAfter(new \DateInterval('PT1H30M'));
		$after = microtime(true);
		$this->assertExpiresBetween($before, $after, 5400, $item);
		$negative = new \DateInterval('PT30S');
		$negative->invert = 1;
		$before = microtime(true);
		$item->expiresAfter($negative);
		$after = microtime(true);
		$this->assertExpiresBetween($before, $after, -30, $item);
	}
}
