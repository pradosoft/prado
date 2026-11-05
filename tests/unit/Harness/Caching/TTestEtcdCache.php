<?php

/**
 * TTestEtcdCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Caching;

use Prado\Caching\TEtcdCache;

/**
 * TTestEtcdCache is a {@see TEtcdCache} harness exposing the serialized-string contract and
 * the protected {@see request()} HTTP seam. The clock is fakeable via
 * {@see TTestCacheClockTrait}. Tests drive {@see request()} through an injected
 * {@see \Prado\IO\HttpClient\THttpClient}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestEtcdCache extends TEtcdCache
{
	use TTestCacheClockTrait;

	public function pubGetSerializedValue(string $key): false|string
	{
		return $this->getSerializedValue($key);
	}

	public function pubSetSerializedValue(string $key, string $value, int $expire): bool
	{
		return $this->setSerializedValue($key, $value, $expire);
	}

	public function pubAddSerializedValue(string $key, string $value, int $expire): bool
	{
		return $this->addSerializedValue($key, $value, $expire);
	}

	public function pubDeleteValue(string $key): bool
	{
		return $this->deleteValue($key);
	}

	public function pubRequest($method, $key, $value = [])
	{
		return $this->request($method, $key, $value);
	}
}
