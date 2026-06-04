<?php

/**
 * TTestMemCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

require_once __DIR__ . '/TTestCacheClockTrait.php';

use Prado\Caching\TMemCache;

/**
 * TTestMemCache is a {@see TMemCache} harness exposing the backend-handle seams
 * ({@see getCacheDirect()} / {@see setCacheDirect()} / {@see newMemcached()}) and the value
 * contract, so a test can inject a handle or assert wiring. The clock is fakeable via
 * {@see TTestCacheClockTrait}. Live-server tests still skip when the `memcached` extension
 * is unavailable.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestMemCache extends TMemCache
{
	use TTestCacheClockTrait;

	public function pubGetCacheDirect(): ?object
	{
		return $this->getCacheDirect();
	}

	public function pubSetCacheDirect($value): void
	{
		$this->setCacheDirect($value);
	}

	public function pubNewMemcached($persistentId): object
	{
		return $this->newMemcached($persistentId);
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
