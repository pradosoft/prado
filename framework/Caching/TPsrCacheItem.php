<?php

/**
 * TPsrCacheItem class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Util\Clock\TClockAwareTrait;
use Psr\Cache\CacheItemInterface;

/**
 * TPsrCacheItem class.
 *
 * TPsrCacheItem is the PSR-6 {@see CacheItemInterface} that {@see TPsrCacheItemPool}
 * hands out. It carries a key, a value, whether the lookup hit, and an optional
 * expiration. {@see TPsrCacheItemPool::save()} turns the expiration into the backing
 * cache's TTL.
 *
 * {@see expiresAt()} stores the instant as given. {@see expiresAfter()} adds the period
 * to the {@see getClock Clock}, which the pool sets to the application clock. `null` in
 * either clears the expiration, so {@see TPsrCacheItemPool::setDefaultExpire DefaultExpire}
 * applies when the item is saved.
 *
 * {@see isHit()} reports the lookup. It stays `false` after {@see set()} on a missed item
 * until the item is saved and fetched again. {@see get()} returns the value held, which is
 * `null` for a missed item until {@see set()}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TPsrCacheItem implements CacheItemInterface
{
	use TClockAwareTrait;

	/** @var string the key */
	private string $_key;

	/** @var mixed the value */
	private mixed $_value;

	/** @var bool whether the lookup hit */
	private bool $_isHit;

	/** @var ?\DateTimeImmutable the expiration; null for the pool's default */
	private ?\DateTimeImmutable $_expiration = null;

	/**
	 * @param string $key the key
	 * @param mixed $value the value; null for a miss
	 * @param bool $isHit whether the lookup hit
	 */
	public function __construct(string $key, mixed $value = null, bool $isHit = false)
	{
		$this->_key = $key;
		$this->_value = $value;
		$this->_isHit = $isHit;
	}

	// ----------------------------------------------------------------- CacheItemInterface

	/**
	 * @return string the key
	 */
	public function getKey(): string
	{
		return $this->_key;
	}

	/**
	 * @return mixed the value; null for a missed item until {@see set()}
	 */
	public function get(): mixed
	{
		return $this->_value;
	}

	/**
	 * @return bool whether the lookup hit
	 */
	public function isHit(): bool
	{
		return $this->_isHit;
	}

	/**
	 * @param mixed $value the value to store when the item is saved
	 * @return static the item
	 */
	public function set(mixed $value): static
	{
		$this->_value = $value;
		return $this;
	}

	/**
	 * @param ?\DateTimeInterface $expiration the instant after which the item is expired; null for the pool's default
	 * @return static the item
	 */
	public function expiresAt(?\DateTimeInterface $expiration): static
	{
		$this->_expiration = $expiration === null ? null : \DateTimeImmutable::createFromInterface($expiration);
		return $this;
	}

	/**
	 * Sets the expiration to a period after the {@see getClock Clock}'s now.
	 * @param null|\DateInterval|int $time the seconds or interval until expiry; null for the pool's default
	 * @return static the item
	 */
	public function expiresAfter(int|\DateInterval|null $time): static
	{
		if ($time === null) {
			$this->_expiration = null;
			return $this;
		}
		$now = $this->getClock()->now();
		if (is_int($time)) {
			$interval = new \DateInterval('PT' . abs($time) . 'S');
			$this->_expiration = $time < 0 ? $now->sub($interval) : $now->add($interval);
		} else {
			$this->_expiration = $now->add($time);
		}
		return $this;
	}

	// --------------------------------------------------------------- accessors

	/**
	 * @return ?\DateTimeImmutable the expiration; null when the pool's default applies
	 */
	public function getExpiration(): ?\DateTimeImmutable
	{
		return $this->_expiration;
	}
}
