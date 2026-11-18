<?php

/**
 * TTestCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Caching;

use Prado\Caching\TCache;

/**
 * TTestCache is a concrete, array-backed {@see TCache} harness for unit tests.
 *
 * {@see TCache} is abstract; this harness implements the storage contract over a plain
 * `$store` array so the base-class behavior (key prefixing/hashing, dependency wrapping,
 * ArrayAccess, primary-cache registration) can be exercised directly.
 * {@see setFakeNow FakeNow} pins the time that expiry reads, for deterministic expiry tests.
 * {@see generateUniqueKey()} is reachable through {@see pubGenerateUniqueKey()}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 */
class TTestCache extends TCache
{
	/** @var array<string, array{0: mixed, 1: int}> stored payload + absolute expiry per key */
	public array $store = [];

	/** @var ?int the Unix timestamp expiry reads; null for the real clock */
	private ?int $_fakeNow = null;

	public static function getIsAvailable(): bool
	{
		return true;
	}

	/**
	 * @param ?int $value the Unix timestamp expiry reads, or null for the real clock
	 */
	public function setFakeNow(?int $value): void
	{
		$this->_fakeNow = $value;
	}

	/**
	 * @return ?int the pinned timestamp, or null when none is set
	 */
	public function getFakeNow(): ?int
	{
		return $this->_fakeNow;
	}

	/**
	 * @return int the pinned timestamp, or the real time when none is set
	 */
	protected function now(): int
	{
		return $this->_fakeNow ?? time();
	}

	protected function getValue($key)
	{
		if (!isset($this->store[$key])) {
			return false;
		}
		[$data, $expire] = $this->store[$key];
		if ($expire > 0 && $expire <= $this->now()) {
			unset($this->store[$key]);
			return false;
		}
		return $data;
	}

	protected function setValue($key, $value, $expire)
	{
		$this->store[$key] = [$value, (int) $expire > 0 ? $this->now() + (int) $expire : 0];
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

	public function pubGenerateUniqueKey(string $key): string
	{
		return $this->generateUniqueKey($key);
	}
}
