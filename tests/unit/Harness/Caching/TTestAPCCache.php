<?php

/**
 * TTestAPCCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

require_once __DIR__ . '/TTestCacheClockTrait.php';

use Prado\Caching\TAPCCache;

/**
 * TTestAPCCache is a {@see TAPCCache} harness exposing its protected value contract. The
 * clock is fakeable via {@see TTestCacheClockTrait}. Live-store tests still skip when the
 * `apcu` extension is unavailable.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestAPCCache extends TAPCCache
{
	use TTestCacheClockTrait;

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
