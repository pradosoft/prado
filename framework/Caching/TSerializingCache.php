<?php

/**
 * TSerializingCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;

/**
 * TSerializingCache class
 *
 * TSerializingCache is an intermediate abstract base class that sits between
 * {@see \Prado\Caching\TCache} and cache backends whose storage layer expects
 * plain strings (e.g. database BLOBs, HTTP key-value APIs).
 *
 * It intercepts the mixed-typed `getValue`/`setValue`/`addValue` calls from
 * {@see \Prado\Caching\TCache}, applies serialization or deserialization,
 * then forwards a `string` to or from the three new abstract storage methods
 * that child classes must implement:
 * - {@see getSerializedValue()} — retrieve a raw serialized string.
 * - {@see setSerializedValue()} — store a raw serialized string.
 * - {@see addSerializedValue()} — conditionally store a raw serialized string.
 *
 * Attached behaviors take priority over the configured format: on every
 * serialize/unserialize call, {@see dySerialize()} and {@see dyUnserialize()}
 * are raised first. If a behavior returns a value that differs from the
 * sentinel default, that result is used directly and the
 * {@see setSerializationType SerializationType} setting is bypassed.
 *
 * The fallback serialization format is controlled by
 * {@see setSerializationType SerializationType}:
 * - `'PHP'` — PHP's native {@see serialize()} / {@see unserialize()} (default).
 * - `'JSON'` — {@see json_encode()} / {@see json_decode()}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 * @method string dySerialize(object $sentinel, mixed $value) Raised before serializing a value; return a string (different from `$sentinel`) to override the configured serialization type.
 * @method mixed dyUnserialize(object $sentinel, string $data) Raised before unserializing data; return any non-sentinel value to override the configured serialization type.
 */
abstract class TSerializingCache extends TCache
{
	/** serialization type constant for PHP native serialize/unserialize */
	public const SERIALIZATION_PHP = 'PHP';

	/** serialization type constant for JSON encode/decode */
	public const SERIALIZATION_JSON = 'JSON';

	/** serialization type constant for JSON encode/decode */
	public const DEFAULT_SERIALIZATION_TYPE = self::SERIALIZATION_PHP;

	/** @var string the active serialization type */
	private string $_serializationType = '';

	// =========================================================================
	// Lifecycle
	// =========================================================================

	public function __construct()
	{
		$this->setSerializationTypeDirect(static::DEFAULT_SERIALIZATION_TYPE);
		parent::__construct();
	}

	// =========================================================================
	// Property Getters/Setters
	// =========================================================================

	/**
	 * @return string the serialization type. Defaults to `'PHP'`.
	 */
	public function getSerializationType(): string
	{
		return $this->_serializationType;
	}

	/**
	 * Sets the serialization type used when no behavior handles {@see dySerialize()}.
	 * @param string $value one of `'PHP'` or `'JSON'`.
	 * @throws TInvalidDataValueException if the value is not a recognized type.
	 */
	public function setSerializationType(string $value): void
	{
		$value = TPropertyValue::ensureString($value);
		if (!in_array($value, [self::SERIALIZATION_PHP, self::SERIALIZATION_JSON], true)) {
			throw new TInvalidDataValueException('serializingcache_type_invalid', $value);
		}
		$this->setSerializationTypeDirect($value);
	}

	/**
	 * Stores the serialization type without validation.
	 * @param string $value the serialization type.
	 */
	protected function setSerializationTypeDirect(string $value): void
	{
		$this->_serializationType = $value;
	}

	// =========================================================================
	// Serialization Helpers
	// =========================================================================

	/**
	 * Serializes a mixed value to a string for storage.
	 * {@see dySerialize()} is always raised first; if a behavior returns a value
	 * that differs from the sentinel, that string is used. Otherwise the
	 * configured {@see getSerializationType SerializationType} is applied.
	 * @param mixed $value the value to serialize.
	 * @return string the serialized string.
	 */
	protected function serializeValue(mixed $value): string
	{
		$newValue = $this->dySerialize($value);
		if ($newValue !== $value) {
			return (string) $newValue;
		}
		return $this->getSerializationType() === self::SERIALIZATION_JSON
			? json_encode($value)
			: serialize($value);
	}

	/**
	 * Restores a mixed value from a serialized string.
	 * {@see dyUnserialize()} is always raised first; if a behavior returns a value
	 * that differs from the sentinel, that value is used. Otherwise the
	 * configured {@see getSerializationType SerializationType} is applied.
	 * @param string $data the serialized string to restore.
	 * @return mixed the unserialized value.
	 */
	protected function unserializeValue(string $data): mixed
	{
		$newData = $this->dyUnserialize($data);
		if ($newData !== $data) {
			return $newData;
		}
		return $this->getSerializationType() === self::SERIALIZATION_JSON
			? json_decode($data, true)
			: unserialize($data);
	}

	// =========================================================================
	// Implementation of the TCache abstract contract
	// =========================================================================

	/**
	 * Retrieves and deserializes a value from cache.
	 * Calls {@see getSerializedValue()} and passes the result through
	 * {@see unserializeValue()}; returns `false` on a cache miss.
	 * @param string $key a unique key identifying the cached value.
	 * @return false|mixed the stored value, or `false` on a miss or expiry.
	 */
	final protected function getValue($key)
	{
		$raw = $this->getSerializedValue($key);
		return $raw === false ? false : $this->unserializeValue($raw);
	}

	/**
	 * Serializes a value and stores it via {@see setSerializedValue()}.
	 * @param string $key the key identifying the value to be cached.
	 * @param mixed $value the value to be cached.
	 * @param int $expire the number of seconds until expiry; 0 means never expire.
	 * @return bool `true` on success.
	 */
	final protected function setValue($key, $value, $expire)
	{
		return $this->setSerializedValue($key, $this->serializeValue($value), $expire);
	}

	/**
	 * Serializes a value and conditionally stores it via {@see addSerializedValue()}.
	 * @param string $key the key identifying the value to be cached.
	 * @param mixed $value the value to be cached.
	 * @param int $expire the number of seconds until expiry; 0 means never expire.
	 * @return bool `true` on success.
	 */
	final protected function addValue($key, $value, $expire)
	{
		return $this->addSerializedValue($key, $this->serializeValue($value), $expire);
	}

	// =========================================================================
	// Subclass Abstract Contract
	// =========================================================================

	/**
	 * Retrieves the raw serialized string for the given key from the storage backend.
	 * Uniqueness and dependency are handled by {@see getValue()}; implement storage retrieval only.
	 * @param string $key a unique key identifying the cached value.
	 * @return false|string the serialized string, or `false` if not in the cache or expired.
	 */
	abstract protected function getSerializedValue(string $key): false|string;

	/**
	 * Stores a serialized string in the storage backend; replaces any existing value for the key.
	 * Uniqueness and dependency are handled by {@see setValue()}; implement storage write only.
	 * @param string $key the key identifying the value to be cached.
	 * @param string $value the serialized value to store.
	 * @param int $expire the number of seconds until expiry; 0 means never expire.
	 * @return bool `true` if successfully stored, `false` otherwise.
	 */
	abstract protected function setSerializedValue(string $key, string $value, int $expire): bool;

	/**
	 * Stores a serialized string only if the key does not already exist in the cache.
	 * Nothing is written and `false` is returned if the key is already present.
	 * Uniqueness and dependency are handled by {@see addValue()}; implement storage write only.
	 * @param string $key the key identifying the value to be cached.
	 * @param string $value the serialized value to store.
	 * @param int $expire the number of seconds until expiry; 0 means never expire.
	 * @return bool `true` if successfully stored, `false` if the key already existed or the write failed.
	 */
	abstract protected function addSerializedValue(string $key, string $value, int $expire): bool;
}
