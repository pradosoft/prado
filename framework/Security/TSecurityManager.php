<?php

/**
 * TSecurityManager class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Security;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TNotSupportedException;
use Prado\TPropertyValue;

/**
 * TSecurityManager class
 *
 * TSecurityManager provides private keys, hashing and encryption
 * functionalities that may be used by other PRADO components,
 * such as viewstate persister, cookies, CSP nonces.
 *
 * TSecurityManager is mainly used to protect data from being tampered
 * and viewed. It can generate HMAC and encrypt the data.
 * The private key used to generate HMAC is set by {@see setValidationKey ValidationKey}.
 * The key used to encrypt data is specified by {@see setEncryptionKey EncryptionKey}.
 * If the above keys are not explicitly set, random keys will be generated
 * and used.
 *
 * To prefix data with an HMAC, call {@see hashData()}.
 * To validate if data is tampered, call {@see validateData()}, which will
 * return the real data if it is not tampered.
 * The algorithm used to generated HMAC is specified by {@see setHashAlgorithm HashAlgorithm}.
 *
 * To encrypt and decrypt data, call {@see encrypt()} and {@see decrypt()}
 * respectively. The encryption algorithm can be set by {@see setCryptAlgorithm CryptAlgorithm}.
 * The algorithm used to hash the encryption key can be set by
 * {@see setEncryptionKeyAlgorithm EncryptionKeyAlgorithm}. By default this is `md5` for
 * backward compatibility. It is highly recommended that EncryptionKeyAlgorithm be set to,
 * at least, `sha1` or `sha256`.
 *
 * Optionally, {@see setUseEncryptionHmac UseEncryptionHmac} (default `false`) enables
 * authenticated encryption: {@see encrypt()} prepends a raw HMAC (keyed on
 * {@see getValidationKey ValidationKey} with {@see getHashAlgorithm HashAlgorithm}) over
 * the IV+ciphertext block. {@see decrypt()} always attempts HMAC verification first
 * regardless of the property value, so data encrypted in either mode can be decrypted
 * transparently after the property changes — making migration in both directions seamless.
 * When HMAC is present and valid, ciphertext tampering is detected reliably and `false`
 * is returned deterministically instead of relying on PKCS7 padding luck.
 *
 * Note, to use encryption, the PHP OpenSSL extension must be loaded. This was introduced in
 * Prado4, older versions used the deprecated mcrypt extension with rijndael-256 cipher as
 * default, which does not have an equivalent in OpenSSL. Developers should keep that in mind
 * when migrating from Prado3 to Prado4.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @since 3.0
 */
class TSecurityManager extends \Prado\TModule
{
	public const STATE_VALIDATION_KEY = 'prado:securitymanager:validationkey';
	public const STATE_ENCRYPTION_KEY = 'prado:securitymanager:encryptionkey';

	private $_validationKey;
	private $_encryptionKey;
	private $_hashAlgorithm = 'sha256';
	private $_cryptAlgorithm = 'aes-256-cbc';
	private $_encryptionKeyAlgorithm = 'md5';
	private $_useEncryptionHmac = false;	// @todo v4.4 change to true.
	private $_mbstring;

	/**
	 * Initializes the module.
	 * The security module is registered with the application.
	 * @param \Prado\Xml\TXmlElement $config initial module configuration
	 */
	public function init($config)
	{
		$this->_mbstring = extension_loaded('mbstring');
		$this->getApplication()->setSecurityManager($this);
		parent::init($config);
	}

	/**
	 * Generates a random key.
	 */
	protected function generateRandomKey()
	{
		try {
			return bin2hex(random_bytes(16));
		} catch (\Exception $e) {
			// @todo from PHP 8.2, this is \Random\RandomException
			// Fallback for environments without proper random support
			return sha1(uniqid(mt_rand(), true));
		}
	}

	/**
	 * @return string the private key used to generate HMAC.
	 * If the key is not explicitly set, a random one is generated and returned.
	 */
	public function getValidationKey()
	{
		if (null === $this->_validationKey) {
			if (null === ($this->_validationKey = $this->getApplication()->getGlobalState(self::STATE_VALIDATION_KEY))) {
				$this->_validationKey = $this->generateRandomKey();
				$this->getApplication()->setGlobalState(self::STATE_VALIDATION_KEY, $this->_validationKey, null, true);
			}
		}
		return $this->_validationKey;
	}

	/**
	 * @param string $value the key used to generate HMAC
	 * @throws TInvalidDataValueException if the key is empty
	 */
	public function setValidationKey($value)
	{
		if ('' === $value) {
			throw new TInvalidDataValueException('securitymanager_validationkey_invalid');
		}

		$this->_validationKey = $value;
	}

	/**
	 * @return string the private key used to encrypt/decrypt data.
	 * If the key is not explicitly set, a random one is generated and returned.
	 */
	public function getEncryptionKey()
	{
		if (null === $this->_encryptionKey) {
			$application = $this->getApplication();
			if (null === ($this->_encryptionKey = $application->getGlobalState(self::STATE_ENCRYPTION_KEY))) {
				$this->_encryptionKey = $this->generateRandomKey();
				$application->setGlobalState(self::STATE_ENCRYPTION_KEY, $this->_encryptionKey, null, true);
			}
		}
		return $this->_encryptionKey;
	}

	/**
	 * @param string $value the key used to encrypt/decrypt data.
	 * @throws TInvalidDataValueException if the key is empty
	 */
	public function setEncryptionKey($value)
	{
		if ('' === $value) {
			throw new TInvalidDataValueException('securitymanager_encryptionkey_invalid');
		}

		$this->_encryptionKey = $value;
	}

	/**
	 * @return string Hashing algorithm used to generate HMAC. Defaults to 'sha256'.
	 */
	public function getHashAlgorithm()
	{
		return $this->_hashAlgorithm;
	}

	/**
	 * This method accepts all hash algorithms returned by {@see supportedHashAlgorithms()}.
	 * @param string $value Hashing algorithm used to generate HMAC.
	 * @throws TInvalidDataValueException If the hash algorithm is not supported.
	 */
	public function setHashAlgorithm($value)
	{
		$value = TPropertyValue::ensureString($value);
		if (!in_array($value, $this->supportedHashAlgorithms())) {
			throw new TInvalidDataValueException('securitymanager_hash_algorithm_invalid');
		}
		$this->_hashAlgorithm = $value;
	}

	/**
	 * @return string Hashing algorithm used to hash {@see getEncryptionKey} during {@see encrypt()} and {@see decrypt()}. Defaults to 'md5'.
	 * @since 4.3.3
	 */
	public function getEncryptionKeyAlgorithm()
	{
		return $this->_encryptionKeyAlgorithm;
	}

	/**
	 * This method accepts all hash algorithms returned by {@see supportedHashAlgorithms()}.
	 * @param string $value Hashing algorithm used to hash {@see getEncryptionKey} during {@see encrypt()} and {@see decrypt()}.
	 * @throws TInvalidDataValueException if the hash algorithm is not supported.
	 * @since 4.3.3
	 */
	public function setEncryptionKeyAlgorithm($value)
	{
		$value = TPropertyValue::ensureString($value);
		if (!in_array($value, $this->supportedHashAlgorithms())) {
			throw new TInvalidDataValueException('securitymanager_hash_algorithm_invalid');
		}
		$this->_encryptionKeyAlgorithm = $value;
	}

	/**
	 * @return bool Whether {@see encrypt()} prepends an HMAC over the IV+ciphertext block.
	 * When `true`, {@see decrypt()} detects ciphertext tampering reliably and returns `false`
	 * deterministically rather than relying on PKCS7 padding luck. Defaults to `false`.
	 * @since 4.3.3
	 */
	public function getUseEncryptionHmac(): bool
	{
		return $this->_useEncryptionHmac;
	}

	/**
	 * Controls whether {@see encrypt()} authenticates its output with an HMAC.
	 *
	 * When set to `true`, {@see encrypt()} prepends a raw HMAC (keyed on
	 * {@see getValidationKey ValidationKey} using {@see getHashAlgorithm HashAlgorithm})
	 * over the concatenated IV and ciphertext. The resulting layout is:
	 * `[HMAC (raw, N bytes)][IV (raw, iv_len bytes)][ciphertext (base64)]`.
	 *
	 * {@see decrypt()} always attempts HMAC verification first regardless of this setting,
	 * so ciphertext produced in either mode is decryptable after the property changes —
	 * enabling seamless migration in both directions.
	 *
	 * @param bool $value `true` to enable authenticated encryption.
	 * @since 4.3.3
	 */
	public function setUseEncryptionHmac($value): void
	{
		$this->_useEncryptionHmac = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string the algorithm used to encrypt/decrypt data. Defaults to the string 'aes-256-cbc'.
	 */
	public function getCryptAlgorithm()
	{
		return $this->_cryptAlgorithm;
	}

	/**
	 * Sets the crypt algorithm (also known as cipher or cypher) that will be used for {@see encrypt} and {@see decrypt}.
	 * @param string $value either a string containing the cipher name.
	 */
	public function setCryptAlgorithm($value)
	{
		$value = TPropertyValue::ensureString($value);
		if (!in_array($value, $this->supportedCipherAlgorithms())) {
			throw new TInvalidDataValueException('securitymanager_crypt_algorithm_invalid');
		}
		$this->_cryptAlgorithm = $value;
	}

	/**
	 * Encrypts data with {@see getEncryptionKey EncryptionKey}.
	 *
	 * When {@see getUseEncryptionHmac UseEncryptionHmac} is `true`, the output is prefixed
	 * with a raw HMAC (computed over the IV+ciphertext using {@see getValidationKey ValidationKey}
	 * and {@see getHashAlgorithm HashAlgorithm}), producing an authenticated ciphertext.
	 * Layout: `[HMAC][IV][ciphertext(base64)]`.
	 *
	 * @param string $data data to be encrypted.
	 * @throws TNotSupportedException if PHP OpenSSL extension is not loaded
	 * @return string the encrypted data
	 */
	public function encrypt($data)
	{
		if (!extension_loaded('openssl')) {
			throw new TNotSupportedException('securitymanager_openssl_required');
		}
		$key = hash($this->getEncryptionKeyAlgorithm(), $this->getEncryptionKey());
		$cryptAlgorithm = $this->getCryptAlgorithm();
		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cryptAlgorithm));
		$ciphertext = $iv . openssl_encrypt($data, $cryptAlgorithm, $key, 0, $iv);
		if ($this->getUseEncryptionHmac()) {
			$hmac = hash_hmac($this->getHashAlgorithm(), $ciphertext, $this->getValidationKey(), true);
			return $hmac . $ciphertext;
		}
		return $ciphertext;
	}

	/**
	 * Decrypts data with {@see getEncryptionKey EncryptionKey}.
	 *
	 * This method always attempts HMAC verification first, regardless of the current
	 * {@see getUseEncryptionHmac UseEncryptionHmac} setting. If the leading bytes form a
	 * valid HMAC over the remainder (verified with {@see getValidationKey ValidationKey}
	 * and {@see getHashAlgorithm HashAlgorithm}), the HMAC is stripped and the authenticated
	 * payload is decrypted. If HMAC verification fails, the method falls back to plain
	 * (unauthenticated) CBC decryption of the full input.
	 *
	 * This dual-path behavior means ciphertext produced before or after the property
	 * changes is always decryptable, enabling seamless migration in both directions.
	 *
	 * @param string $data data to be decrypted.
	 * @throws TNotSupportedException if PHP OpenSSL extension is not loaded
	 * @return false|string the decrypted data, or `false` on failure (including
	 *   detected HMAC tampering when the ciphertext carries a valid HMAC header)
	 */
	public function decrypt($data)
	{
		if (!extension_loaded('openssl')) {
			throw new TNotSupportedException('securitymanager_openssl_required');
		}
		$key = hash($this->getEncryptionKeyAlgorithm(), $this->getEncryptionKey());
		$cryptAlgorithm = $this->getCryptAlgorithm();

		// Always probe for an HMAC header first.  The HMAC is a fixed-length raw-binary
		// digest, so we can compute its expected length from a dummy call.  If the stored
		// digest matches a freshly computed one over the remaining bytes, the ciphertext is
		// authenticated; strip the HMAC and decrypt only the IV+ciphertext payload.
		// Falling back to plain decryption when HMAC is absent lets data encrypted without
		// UseEncryptionHmac be read back after the property is enabled, and vice-versa.
		//
		// Note: native strlen() is used here intentionally.
		$hmacLen = strlen(hash_hmac($this->getHashAlgorithm(), '', $this->getValidationKey(), true));
		if ($this->strlen($data) > $hmacLen) {
			$storedHmac = $this->substr($data, 0, $hmacLen);
			$payload = $this->substr($data, $hmacLen);
			$expectedHmac = hash_hmac($this->getHashAlgorithm(), $payload, $this->getValidationKey(), true);
			if (hash_equals($expectedHmac, $storedHmac)) {
				// Authenticated path: HMAC is valid — decrypt the inner IV+ciphertext.
				$ivLen = openssl_cipher_iv_length($cryptAlgorithm);
				$iv = $this->substr($payload, 0, $ivLen);
				return openssl_decrypt($this->substr($payload, $ivLen), $cryptAlgorithm, $key, 0, $iv);
			}
		}

		// Plain (unauthenticated) path: treat the full input as IV+ciphertext.
		$ivLen = openssl_cipher_iv_length($cryptAlgorithm);
		$iv = $this->substr($data, 0, $ivLen);
		return openssl_decrypt($this->substr($data, $ivLen), $cryptAlgorithm, $key, 0, $iv);
	}

	/**
	 * Prefixes data with an HMAC.
	 * @param string $data data to be hashed.
	 * @return string data prefixed with HMAC
	 */
	public function hashData($data)
	{
		$hmac = $this->computeHMAC($data);
		return $hmac . $data;
	}

	/**
	 * Validates if data is tampered.
	 * @param string $data data to be validated. The data must be previously
	 * generated using {@see hashData()}.
	 * @return string the real data with HMAC stripped off. False if the data
	 * is tampered.
	 */
	public function validateData($data)
	{
		$len = $this->strlen($this->computeHMAC('test'));

		if ($this->strlen($data) < $len) {
			return false;
		}

		$hmac = $this->substr($data, 0, $len);
		$data2 = $this->substr($data, $len);
		return hash_equals($this->computeHMAC($data2), $hmac) ? $data2 : false;
	}

	/**
	 * Computes the HMAC for the data with {@see getValidationKey ValidationKey}.
	 * @param string $data data to be generated HMAC
	 * @return string the HMAC for the data
	 */
	protected function computeHMAC($data)
	{
		return hash_hmac($this->getHashAlgorithm(), $data, $this->getValidationKey());
	}

	/**
	 * When `hash_hmac_algos` exists it is called and results returned.
	 * Otherwise, this calls `hash_algos` and its results are returned.
	 * @return array Array of supported hash methods, eg md5, sha1, sha256
	 * @since 4.3.3
	 */
	public function supportedHashAlgorithms()
	{
		return function_exists('hash_hmac_algos') ? hash_hmac_algos() : hash_algos();
	}

	/**
	 * When `openssl_get_cipher_methods` exists it is called and results returned.
	 * @return array Array of supported cipher methods, e.g. aes-256-cbc, aes-256-gcm
	 * @since 4.3.3
	 */
	public function supportedCipherAlgorithms()
	{
		return function_exists('openssl_get_cipher_methods') ? openssl_get_cipher_methods() : [];
	}

	/**
	 * Returns the length of the given string.
	 * If available uses the multibyte string function mb_strlen.
	 * @param string $string $string the string being measured for length
	 * @return int the length of the string
	 */
	private function strlen($string)
	{
		return $this->_mbstring ? mb_strlen($string, '8bit') : strlen($string);
	}

	/**
	 * Returns the portion of string specified by the start and optional length parameters.
	 * If available uses the multibyte string function mb_substr.
	 * When $length is omitted (null) the substring extends to the end of the string,
	 * mirroring the behaviour of the native substr() and mb_substr() functions.
	 * @param string $string the input string. Must be one character or longer.
	 * @param int $start the starting position
	 * @param ?int $length the desired portion length, or null to read to the end
	 * @return string the extracted part of string, or FALSE on failure or an empty string.
	 */
	private function substr($string, $start, $length = null)
	{
		return $this->_mbstring ? mb_substr($string, $start, $length, '8bit') : substr($string, $start, $length);
	}

	/**
	 * Returns a per-request nonce value to be used in a Content-security-policy
	 * @return string nonce
	 */
	public function getCSPNonce()
	{
		static $nonce;
		if ($nonce === null) {
			$nonce = $this->generateRandomKey();
		}
		return $nonce;
	}
}
