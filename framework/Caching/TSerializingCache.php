<?php

/**
 * TSerializingCache class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Caching;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Security\TSecurityManager;
use Prado\TPropertyValue;
use Prado\Util\Traits\TInitializedTrait;

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
	use TInitializedTrait;

	/** serialization type constant for PHP native serialize/unserialize */
	public const SERIALIZATION_PHP = 'PHP';

	/** serialization type constant for JSON encode/decode */
	public const SERIALIZATION_JSON = 'JSON';

	/** serialization type constant for JSON encode/decode */
	public const DEFAULT_SERIALIZATION_TYPE = self::SERIALIZATION_PHP;

	/** encoding constant: store the payload verbatim (no encoding) */
	public const ENCODING_NONE = 'None';

	/** encoding constant: base64-encode the payload */
	public const ENCODING_BASE64 = 'Base64';

	/** encoding constant: hexadecimal-encode the payload */
	public const ENCODING_HEX = 'Hex';

	/** default payload encoding */
	public const DEFAULT_ENCODING = self::ENCODING_NONE;

	/** @var string the active serialization type */
	private string $_serializationType = '';

	/** @var bool whether the serialized payload is encrypted */
	private bool $_encrypt = false;

	/** @var string the active payload encoding */
	private string $_encoding = '';

	/** @var string|TSecurityManager security manager module id, or the instance; '' = application security manager */
	private string|TSecurityManager $_securityManager = '';

	// =========================================================================
	// Lifecycle
	// =========================================================================

	public function __construct()
	{
		$this->setSerializationTypeDirect(static::DEFAULT_SERIALIZATION_TYPE);
		$this->setEncodingDirect(static::DEFAULT_ENCODING);
		parent::__construct();
	}

	/**
	 * Initializes the module. When {@see getEncrypt Encrypt} is enabled, the
	 * {@see getSecurityManager SecurityManager} is resolved and validated up front so
	 * that a misconfigured module id fails fast. After initialization the
	 * format-defining properties ({@see setSerializationType SerializationType},
	 * {@see setEncrypt Encrypt}, {@see setEncoding Encoding},
	 * {@see setSecurityManager SecurityManager}) can no longer be changed.
	 * @param mixed $config module configuration.
	 */
	public function init($config)
	{
		if ($this->getEncrypt()) {
			$this->getSecurityManager();
		}
		parent::init($config);
		$this->markInitialized();
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
	public function setSerializationType($value)
	{
		$this->assertUninitialized('SerializationType');
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

	/**
	 * @return bool whether the serialized payload is encrypted with the
	 *   {@see getSecurityManager SecurityManager}. Defaults to `false`.
	 */
	public function getEncrypt(): bool
	{
		return $this->_encrypt;
	}

	/**
	 * @param mixed $value whether to encrypt the serialized payload.
	 */
	public function setEncrypt($value)
	{
		$this->assertUninitialized('Encrypt');
		$this->setEncryptDirect(TPropertyValue::ensureBoolean($value));
	}

	/**
	 * Stores the encrypt flag without conversion.
	 * @param bool $value whether to encrypt the serialized payload.
	 */
	protected function setEncryptDirect(bool $value): void
	{
		$this->_encrypt = $value;
	}

	/**
	 * @return string the payload encoding, one of {@see ENCODING_NONE},
	 *   {@see ENCODING_BASE64}, {@see ENCODING_HEX}. Defaults to `'None'`.
	 */
	public function getEncoding(): string
	{
		return $this->_encoding;
	}

	/**
	 * Sets the encoding applied to the (possibly encrypted) payload before storage.
	 * @param string $value one of `'None'`, `'Base64'`, or `'Hex'`.
	 * @throws TInvalidDataValueException if the value is not a recognized encoding.
	 */
	public function setEncoding($value)
	{
		$this->assertUninitialized('Encoding');
		$value = TPropertyValue::ensureString($value);
		if (!in_array($value, [self::ENCODING_NONE, self::ENCODING_BASE64, self::ENCODING_HEX], true)) {
			throw new TInvalidDataValueException('serializingcache_encoding_invalid', $value);
		}
		$this->setEncodingDirect($value);
	}

	/**
	 * Stores the encoding without validation.
	 * @param string $value the encoding.
	 */
	protected function setEncodingDirect(string $value): void
	{
		$this->_encoding = $value;
	}

	/**
	 * Returns the security manager used for {@see getEncrypt encryption}. When the
	 * property is the empty string (the default), the application security manager is
	 * returned. When it is a module id, that module is resolved and validated.
	 * @return TSecurityManager the resolved security manager.
	 * @throws TConfigurationException if the configured module id does not resolve to a {@see TSecurityManager}.
	 */
	public function getSecurityManager(): TSecurityManager
	{
		$sm = $this->_securityManager;
		if ($sm === '') {
			return $this->getApplication()->getSecurityManager();
		}
		if (is_string($sm)) {
			$module = $this->getApplication()->getModule($sm);
			if (!($module instanceof TSecurityManager)) {
				throw new TConfigurationException('serializingcache_securitymanager_invalid', $sm);
			}
			$this->_securityManager = $module;
			return $module;
		}
		return $sm;
	}

	/**
	 * Sets the security manager used for encryption, as a module id or an instance.
	 * The default empty string means the application security manager is used.
	 * @param string|TSecurityManager $value the security manager module id or instance.
	 * @throws TConfigurationException if the value is neither a string nor a {@see TSecurityManager}.
	 */
	public function setSecurityManager($value)
	{
		$this->assertUninitialized('SecurityManager');
		if (!is_string($value) && !($value instanceof TSecurityManager)) {
			throw new TConfigurationException('serializingcache_securitymanager_invalid', $value);
		}
		$this->_securityManager = $value;
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
			$data = (string) $newValue;
		} elseif ($this->getSerializationType() === self::SERIALIZATION_JSON) {
			$data = json_encode($value);
			if ($data === false) {
				throw new TInvalidDataValueException('serializingcache_json_encode_failed', json_last_error_msg());
			}
		} else {
			$data = serialize($value);
		}
		if ($this->getEncrypt()) {
			$data = $this->getSecurityManager()->encrypt($data);
		}
		return $this->encode($data);
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
		$decoded = $this->decode($data);
		if ($decoded === false) {
			return false;
		}
		$data = $decoded;
		if ($this->getEncrypt()) {
			$data = $this->getSecurityManager()->decrypt($data);
			if ($data === false) {
				return false;
			}
		}
		$newData = $this->dyUnserialize($data);
		if ($newData !== $data) {
			return $newData;
		}
		return $this->getSerializationType() === self::SERIALIZATION_JSON
			? json_decode($data, true)
			: unserialize($data);
	}

	/**
	 * Encodes a (possibly encrypted) payload into a text-safe string per
	 * {@see getEncoding Encoding}.
	 * @param string $data the raw payload.
	 * @return string the encoded payload.
	 */
	protected function encode(string $data): string
	{
		return match ($this->getEncoding()) {
			self::ENCODING_BASE64 => base64_encode($data),
			self::ENCODING_HEX => bin2hex($data),
			default => $data,
		};
	}

	/**
	 * Reverses {@see encode()}.
	 * @param string $data the encoded payload.
	 * @return false|string the decoded payload, or `false` if the input is not validly encoded.
	 */
	protected function decode(string $data): false|string
	{
		switch ($this->getEncoding()) {
			case self::ENCODING_BASE64:
				return base64_decode($data, true);
			case self::ENCODING_HEX:
				return (strlen($data) % 2 === 0 && ctype_xdigit($data)) ? hex2bin($data) : false;
			default:
				return $data;
		}
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
	protected function getValue($key)
	{
		$raw = $this->getSerializedValue((string) $key);
		return $raw === false ? false : $this->unserializeValue($raw);
	}

	/**
	 * Serializes a value and stores it via {@see setSerializedValue()}.
	 * @param string $key the key identifying the value to be cached.
	 * @param mixed $value the value to be cached.
	 * @param int $expire the number of seconds until expiry; 0 means never expire.
	 * @return bool `true` on success.
	 */
	protected function setValue($key, $value, $expire)
	{
		return $this->setSerializedValue((string) $key, $this->serializeValue($value), (int) $expire);
	}

	/**
	 * Serializes a value and conditionally stores it via {@see addSerializedValue()}.
	 * @param string $key the key identifying the value to be cached.
	 * @param mixed $value the value to be cached.
	 * @param int $expire the number of seconds until expiry; 0 means never expire.
	 * @return bool `true` on success.
	 */
	protected function addValue($key, $value, $expire)
	{
		return $this->addSerializedValue((string) $key, $this->serializeValue($value), (int) $expire);
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
