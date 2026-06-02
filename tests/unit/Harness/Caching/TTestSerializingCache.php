<?php

/**
 * TTestSerializingCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

require_once __DIR__ . '/TTestCacheClockTrait.php';

use Prado\Caching\TSerializingCache;

/**
 * TTestSerializingCache is a concrete, array-backed {@see TSerializingCache} harness.
 *
 * {@see TSerializingCache} is abstract; this harness implements the serialized-string
 * storage contract ({@see getSerializedValue()} / {@see setSerializedValue()} /
 * {@see addSerializedValue()}) over a plain `$store` array, so the base-class
 * serialize → encrypt → encode pipeline can be exercised and inspected directly.
 *
 * Exposed seams:
 * - clock ({@see time()} / {@see microtime()}) via {@see $fakeNow} / {@see $fakeMicrotime};
 * - {@see serializeValue()}, {@see unserializeValue()}, {@see encode()}, {@see decode()},
 *   and {@see getSerializedValue()} through `pub*()` accessors;
 * - {@see onlyStored()} returns the single stored raw payload so a test can assert how
 *   encryption/encoding shaped what was persisted.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestSerializingCache extends TSerializingCache
{
	use TTestCacheClockTrait;

	/** @var array<string, string> stored serialized payload per key (raw, inspectable) */
	public array $store = [];

	/** @var array<string, int> absolute expiry per key (0 = never) */
	public array $expires = [];

	public static function getIsAvailable(): bool
	{
		return true;
	}

	/** @return string the single stored raw payload (tests use one key at a time) */
	public function onlyStored(): string
	{
		$payload = reset($this->store);
		return $payload === false ? '' : (string) $payload;
	}

	protected function getSerializedValue(string $key): false|string
	{
		if (!isset($this->store[$key])) {
			return false;
		}
		$expire = $this->expires[$key] ?? 0;
		if ($expire > 0 && $expire <= $this->time()) {
			unset($this->store[$key], $this->expires[$key]);
			return false;
		}
		return $this->store[$key];
	}

	protected function setSerializedValue(string $key, string $value, int $expire): bool
	{
		$this->store[$key] = $value;
		$this->expires[$key] = $expire > 0 ? $this->time() + $expire : 0;
		return true;
	}

	protected function addSerializedValue(string $key, string $value, int $expire): bool
	{
		if ($this->getSerializedValue($key) !== false) {
			return false;
		}
		return $this->setSerializedValue($key, $value, $expire);
	}

	protected function deleteValue($key)
	{
		unset($this->store[$key], $this->expires[$key]);
		return true;
	}

	public function flush()
	{
		$this->store = [];
		$this->expires = [];
		return true;
	}

	// ---------------------------------------------------------------- exposers

	public function pubSerializeValue(mixed $value): string
	{
		return $this->serializeValue($value);
	}

	public function pubUnserializeValue(string $data): mixed
	{
		return $this->unserializeValue($data);
	}

	public function pubEncode(string $data): string
	{
		return $this->encode($data);
	}

	public function pubDecode(string $data): false|string
	{
		return $this->decode($data);
	}

	public function pubGetSerializedValue(string $key): false|string
	{
		return $this->getSerializedValue($key);
	}
}
