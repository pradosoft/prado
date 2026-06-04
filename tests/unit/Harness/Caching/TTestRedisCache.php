<?php

/**
 * TTestRedisCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

require_once __DIR__ . '/TTestCacheClockTrait.php';

use Prado\Caching\TRedisCache;

/**
 * TTestRedisCache is a {@see TRedisCache} harness exposing the backend-handle seams
 * ({@see getCacheDirect()} / {@see setCacheDirect()} / {@see newRedis()}) and the value
 * contract. The clock is fakeable via {@see TTestCacheClockTrait}. Live-server tests still
 * skip when the `redis` extension is unavailable.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestRedisCache extends TRedisCache
{
	use TTestCacheClockTrait;

	public function pubGetCacheDirect(): ?object
	{
		return $this->getCacheDirect();
	}

	public function pubSetCacheDirect(?object $value): void
	{
		$this->setCacheDirect($value);
	}

	public function pubNewRedis(): object
	{
		return $this->newRedis();
	}

	public function pubGetValue(string $key): mixed
	{
		return $this->getValue($key);
	}

	public function pubSetValue(string $key, mixed $value, int $expire): bool
	{
		return $this->setValue($key, $value, $expire);
	}

	public function pubAddValue(string $key, mixed $value, int $expire): bool
	{
		return $this->addValue($key, $value, $expire);
	}

	public function pubDeleteValue(string $key): bool
	{
		return $this->deleteValue($key);
	}
}
