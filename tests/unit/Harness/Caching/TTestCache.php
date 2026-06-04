<?php

/**
 * TTestCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

require_once __DIR__ . '/TTestCacheClockTrait.php';

use Prado\Caching\TCache;

/**
 * TTestCache is a concrete, array-backed {@see TCache} harness for unit tests.
 *
 * {@see TCache} is abstract; this harness implements the storage contract over a plain
 * `$store` array so the base-class behavior (key prefixing/hashing, dependency wrapping,
 * ArrayAccess, primary-cache registration) can be exercised directly. It also exposes the
 * protected encapsulation seams:
 * - the clock ({@see time()} / {@see microtime()}) is overridable via {@see $fakeNow} /
 *   {@see $fakeMicrotime} for deterministic expiry/LRU tests;
 * - {@see generateUniqueKey()}, {@see generateToken()}, {@see hashToken()},
 *   {@see getKeyPrefix()}, and {@see setAppCache()} are reachable through `pub*()` accessors.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestCache extends TCache
{
	use TTestCacheClockTrait;

	/** @var array<string, array{0: mixed, 1: int}> stored payload + absolute expiry per key */
	public array $store = [];

	public static function getIsAvailable(): bool
	{
		return true;
	}

	protected function getValue($key)
	{
		if (!isset($this->store[$key])) {
			return false;
		}
		[$data, $expire] = $this->store[$key];
		if ($expire > 0 && $expire <= $this->time()) {
			unset($this->store[$key]);
			return false;
		}
		return $data;
	}

	protected function setValue($key, $value, $expire)
	{
		$this->store[$key] = [$value, (int) $expire > 0 ? $this->time() + (int) $expire : 0];
		return true;
	}

	protected function addValue($key, $value, $expire)
	{
		if ($this->getValue($key) !== false) {
			return false;
		}
		return $this->setValue($key, $value, $expire);
	}

	protected function deleteValue($key)
	{
		unset($this->store[$key]);
		return true;
	}

	public function flush()
	{
		$this->store = [];
		return true;
	}

	// ---------------------------------------------------------------- exposers

	public function pubGenerateUniqueKey(string $key): string
	{
		return $this->generateUniqueKey($key);
	}

	public function pubGenerateToken(string $key): string
	{
		return $this->generateToken($key);
	}

	public function pubHashToken(string $token): string
	{
		return $this->hashToken($token);
	}

	public function pubGetKeyPrefix(): string
	{
		return $this->getKeyPrefix();
	}

	public function pubSetAppCache(): void
	{
		$this->setAppCache();
	}
}
