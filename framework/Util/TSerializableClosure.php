<?php

/**
 * TSerializableClosure class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Laravel\SerializableClosure\SerializableClosure;

/**
 * TSerializableClosure class
 *
 * TSerializableClosure is a thin wrapper around {@see \Laravel\SerializableClosure\SerializableClosure}
 * that allows a PHP {@see \Closure} to survive {@see serialize()} and {@see unserialize()}, which the
 * language otherwise forbids. This makes closures storable, for example as a cron task handler in the
 * database, or in the cache and session.
 *
 * The wrapped closure is invoked directly on the instance and is recovered with {@see getClosure}:
 * ```php
 * $wrapped = new TSerializableClosure(fn ($x) => $x * 2);
 * echo $wrapped(3); // 6
 *
 * $data = serialize($wrapped);
 * $closure = unserialize($data)->getClosure();
 * echo $closure(3); // 6
 * ```
 *
 * Captured `use` variables, arrow function bindings, `$this`, and the class scope are preserved.
 *
 * Security: unserializing a closure reconstructs its code with `eval()`. Any stored payload is therefore
 * executable code and a tampered payload is remote code execution. The application
 * {@see \Prado\Security\TSecurityManager} configures the HMAC signing key on initialization (an explicit
 * key may also be set via {@see setSecretKey}). Once a key is set, serialized payloads are HMAC-signed and
 * {@see unserialize()} rejects tampered or unsigned payloads with a
 * {@see \Laravel\SerializableClosure\Exceptions\InvalidSignatureException}.
 *
 * The security manager also configures an encrypter via {@see setEncryptionUsing} so the serialized payload
 * is not readable. When an encrypter is set, {@see __serialize} returns the encrypted payload and
 * {@see __unserialize} decrypts it.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TSerializableClosure extends SerializableClosure
{
	/** @var string the serialized-array key holding the encrypted closure payload */
	public const ENCRYPTED_KEY = 'closureencrypt';

	/** @var ?callable encrypts a serialized closure payload string; configured by TSecurityManager */
	protected static $encryptUsing;

	/** @var ?callable decrypts an encrypted closure payload string; configured by TSecurityManager */
	protected static $decryptUsing;

	/**
	 * Sets the encrypter that makes serialized closures unreadable. The application
	 * {@see \Prado\Security\TSecurityManager} sets this on init. Pass null to disable encryption.
	 * @param ?callable $encrypt encrypts a serialized payload string
	 * @param ?callable $decrypt decrypts an encrypted payload string
	 */
	public static function setEncryptionUsing($encrypt, $decrypt)
	{
		static::$encryptUsing = $encrypt;
		static::$decryptUsing = $decrypt;
	}

	/**
	 * Returns the serializable representation. When an encrypter is configured, the inner serializer
	 * (the already-signed closure) is serialized once and that string is encrypted, so the readable
	 * payload becomes a single opaque ciphertext. PHP then wraps this returned array, as its
	 * {@see __serialize} contract requires.
	 * @return array the serialized representation
	 */
	public function __serialize()
	{
		if (static::$encryptUsing !== null) {
			return [self::ENCRYPTED_KEY => (static::$encryptUsing)(serialize($this->serializable))];
		}
		return parent::__serialize();
	}

	/**
	 * Restores the closure, decrypting the payload first when it was encrypted. Decryption yields the
	 * inner serializer, whose unserialization verifies the HMAC signature when a signer is set.
	 * @param array $data the serialized representation
	 * @throws \Prado\Exceptions\TConfigurationException when an encrypted payload has no decrypter, or
	 * when decryption fails (a tampered payload or a changed key)
	 */
	public function __unserialize($data)
	{
		if (array_key_exists(self::ENCRYPTED_KEY, $data)) {
			if (static::$decryptUsing === null) {
				throw new \Prado\Exceptions\TConfigurationException('serializableclosure_no_decrypter');
			}
			$payload = (static::$decryptUsing)($data[self::ENCRYPTED_KEY]);
			if (!is_string($payload)) {
				throw new \Prado\Exceptions\TConfigurationException('serializableclosure_decrypt_failed');
			}
			parent::__unserialize(['serializable' => unserialize($payload)]);
			return;
		}
		parent::__unserialize($data);
	}
}
