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
use Prado\TApplicationMode;
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
 * Cookies are signed with {@see hashCookieData()} and {@see validateCookieData()},
 * keyed by {@see setCookieValidationKey CookieValidationKey}.  Changing
 * CookieValidationKey invalidates every signed cookie and no other data.
 * The algorithm used to generated HMAC is specified by {@see setHashAlgorithm HashAlgorithm}.
 *
 * To encrypt and decrypt data, call {@see encrypt()} and {@see decrypt()}
 * respectively. The encryption algorithm can be set by {@see setCryptAlgorithm CryptAlgorithm}.
 * The algorithm used to hash the encryption key can be set by
 * {@see setEncryptionKeyAlgorithm EncryptionKeyAlgorithm}, which defaults to `sha256`.
 * Prado 4.3 defaulted to `md5`; setting EncryptionKeyAlgorithm to `md5` decrypts data
 * encrypted under that default.
 *
 * {@see setUseEncryptionHmac UseEncryptionHmac} (default `true`) enables
 * authenticated encryption: {@see encrypt()} prepends a raw HMAC (keyed on
 * {@see getValidationKey ValidationKey} with {@see getHashAlgorithm HashAlgorithm}) over
 * the IV+ciphertext block. {@see decrypt()} verifies a valid HMAC prefix whether the
 * property is `true` or `false`. With the property `true`, input without a valid HMAC
 * returns `false`, so altered or stripped ciphertext is rejected. With the property
 * `false`, input without a valid HMAC is decrypted as plain IV+ciphertext. Data encrypted
 * while the property was `false` no longer decrypts once it is `true`; an application
 * keeping such data re-encrypts it before enabling the property, or leaves the property
 * `false` until the data expires.
 *
 * Note, to use encryption, the PHP OpenSSL extension must be loaded. This was introduced in
 * Prado4, older versions used the deprecated mcrypt extension with rijndael-256 cipher as
 * default, which does not have an equivalent in OpenSSL. Developers should keep that in mind
 * when migrating from Prado3 to Prado4.
 *
 * XML configuration style:
 * ```xml
 * <modules>
 *   <module id="security" class="Prado\Security\TSecurityManager"
 *       ValidationKey="your-secret-validation-key"
 *       CookieValidationKey="your-secret-cookie-validation-key"
 *       EncryptionKey="your-secret-encryption-key"
 *       HashAlgorithm="sha256"
 *       CryptAlgorithm="aes-256-cbc"
 *       UseEncryptionHmac="true" />
 * </modules>
 * ```
 *
 * PHP configuration style:
 * ```php
 * return [
 *     'modules' => [
 *         'security' => [
 *             'class' => 'Prado\Security\TSecurityManager',
 *             'properties' => [
 *                 'ValidationKey' => 'your-secret-validation-key',
 *                 'CookieValidationKey' => 'your-secret-cookie-validation-key',
 *                 'EncryptionKey' => 'your-secret-encryption-key',
 *                 'HashAlgorithm' => 'sha256',
 *                 'CryptAlgorithm' => 'aes-256-cbc',
 *                 'UseEncryptionHmac' => 'true',
 *             ],
 *         ],
 *     ],
 * ];
 * ```
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
	private ?string $_cookieValidationKey = null;
	private $_encryptionKey;
	private $_hashAlgorithm = 'sha256';
	private $_cryptAlgorithm = 'aes-256-cbc';
	private $_encryptionKeyAlgorithm = 'sha256';
	private $_useEncryptionHmac = true;
	/**
	 * @var ?string the Content-Security-Policy nonce, generated on first {@see getCSPNonce}
	 * @since 4.4.0
	 */
	private ?string $_cspNonce = null;
	private $_closureSecretKey;
	private ?bool $_closureUnencrypted = null;

	/**
	 * Initializes the module.
	 * The security module is registered with the application.
	 * @param null|array|\Prado\Xml\TXmlElement $config initial module configuration
	 */
	public function init($config)
	{
		$this->setupSerializableClosure();
		$this->setAppSecurityManager();
		parent::init($config);
	}

	/**
	 * Registers this module as the application security manager when an application is available.
	 * Called during {@see init()}; may also be called by behaviors or subclasses.
	 * @since 4.4.0
	 */
	protected function setAppSecurityManager()
	{
		$this->getApplication()?->setSecurityManager($this);
	}

	/**
	 * Resolves the closure protection properties — {@see getClosureSecretKey} and, when
	 * {@see getShouldEncryptClosure}, the {@see encryptClosure}/{@see decryptClosure} encrypter — and
	 * applies them through {@see configureSerializableClosure}. Called during {@see init()}; may also be
	 * called by behaviors or subclasses.
	 * @since 4.4.0
	 */
	protected function setupSerializableClosure()
	{
		$encrypt = $this->getShouldEncryptClosure();
		$this->configureSerializableClosure(
			hash('sha512', $this->getClosureSecretKey()),
			$encrypt ? [$this, 'encryptClosure'] : null,
			$encrypt ? [$this, 'decryptClosure'] : null
		);
	}

	/**
	 * Helper that applies serializable closure protection to {@see \Prado\Util\TSerializableClosure}:
	 * the HMAC signing secret, and an encrypter that makes stored closures
	 * unreadable. {@see setupSerializableClosure} calls this during {@see init()} with the SHA-512 closure
	 * signing secret and the encrypter; behaviors and subclasses may call it with their own values.
	 * @param string $secretKey the closure HMAC signing secret
	 * @param ?callable $encrypt encrypts a serialized payload, or null to disable encryption
	 * @param ?callable $decrypt decrypts an encrypted payload, or null to disable encryption
	 * @since 4.4.0
	 */
	protected function configureSerializableClosure($secretKey, $encrypt = null, $decrypt = null)
	{
		\Prado\Util\TSerializableClosure::setSecretKey($secretKey);
		\Prado\Util\TSerializableClosure::setEncryptionUsing($encrypt, $decrypt);
	}

	/**
	 * The key used to HMAC-sign serializable closures. When not set, it defaults to (backs up to) the
	 * {@see getValidationKey ValidationKey}. The signer is given the SHA-512 hash of this key (see
	 * {@see setupSerializableClosure}), so neither the raw closure key nor the raw validation key is
	 * ever the signing secret directly.
	 * @return string the serializable closure key
	 * @since 4.4.0
	 */
	public function getClosureSecretKey(): string
	{
		return $this->_closureSecretKey ?? $this->getValidationKey();
	}

	/**
	 * @param ?string $value the serializable closure key; null or an empty string restores the default
	 * (the ValidationKey), so the signer is never keyed on an empty secret
	 * @since 4.4.0
	 */
	public function setClosureSecretKey($value)
	{
		$value = ($value === null) ? '' : TPropertyValue::ensureString($value);
		$this->_closureSecretKey = ($value === '') ? null : $value;
	}

	/**
	 * Whether serialized closures are left unencrypted so their code stays readable. `true` never
	 * encrypts, `false` always encrypts, and `null` (the default, "Auto") leaves them unencrypted only
	 * while developing — application mode {@see \Prado\TApplicationMode::Debug Debug} or
	 * {@see \Prado\TApplicationMode::Off Off}, or with no application — and encrypts in
	 * {@see \Prado\TApplicationMode::Normal Normal} and {@see \Prado\TApplicationMode::Performance
	 * Performance}, so encryption is on by default in a running production application. The property is
	 * named for the unencrypted state so the string alias {@see \Prado\TApplicationMode::Debug 'Debug'}
	 * reads naturally: "unencrypted in Debug".
	 * @return ?bool true, false, or null for Auto
	 * @since 4.4.0
	 */
	public function getClosureUnencrypted(): ?bool
	{
		return $this->_closureUnencrypted;
	}

	/**
	 * @param null|bool|string $value true, false, or null for the mode-aware default that leaves closures
	 * unencrypted only in Debug or Off. Only the strings 'Auto' and 'Debug' (case-insensitive) are
	 * aliases for null; any other value is coerced to bool via {@see \Prado\TPropertyValue::ensureBoolean}
	 * (for example 'Normal' or 'Performance' become false, i.e. always encrypt).
	 * @since 4.4.0
	 */
	public function setClosureUnencrypted($value)
	{
		if ($value === null || (is_string($value) && (strcasecmp($value, 'Auto') === 0 || strcasecmp($value, TApplicationMode::Debug) === 0))) {
			$this->_closureUnencrypted = null;
		} else {
			$this->_closureUnencrypted = TPropertyValue::ensureBoolean($value);
		}
	}

	/**
	 * @return bool whether serialized closures should be encrypted given {@see getClosureUnencrypted}
	 * and the application mode. When Auto (null), encrypts only when the application mode is Normal or
	 * Performance, so a running production application is required to encrypt.
	 * @since 4.4.0
	 */
	public function getShouldEncryptClosure(): bool
	{
		$unencrypted = $this->getClosureUnencrypted();
		if ($unencrypted !== null) {
			return !$unencrypted;
		}
		return in_array(
			$this->getApplication()?->getMode(),
			[TApplicationMode::Normal, TApplicationMode::Performance],
			true
		);
	}

	/**
	 * Encrypts a serialized closure payload so it is not readable.
	 * @param string $data the serialized closure payload
	 * @return string the encrypted payload
	 * @since 4.4.0
	 */
	public function encryptClosure($data)
	{
		return $this->encrypt($data);
	}

	/**
	 * Decrypts an encrypted closure payload.
	 * @param string $data the encrypted payload
	 * @return false|string the serialized closure payload, or false on failure
	 * @since 4.4.0
	 */
	public function decryptClosure($data)
	{
		return $this->decrypt($data);
	}

	/**
	 * Generates a random key.
	 */
	protected function generateRandomKey()
	{
		try {
			return bin2hex(random_bytes(16));
		} catch (\Exception $e) { // @codeCoverageIgnore
			// @todo from PHP 8.2, this is \Random\RandomException
			// Fallback for environments without proper random support; random_bytes() throws only without a system source.
			return sha1(uniqid(mt_rand(), true)); // @codeCoverageIgnore
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
	 * @return string the private key used to sign cookies.  When it is not set, the key is
	 *   an HMAC of `prado:cookie` keyed by {@see getValidationKey() ValidationKey}.
	 * @since 4.4.0
	 */
	public function getCookieValidationKey(): string
	{
		return $this->_cookieValidationKey ?? hash_hmac($this->getHashAlgorithm(), 'prado:cookie', $this->getValidationKey());
	}

	/**
	 * @param ?string $value the key used to sign cookies; null derives it from ValidationKey.
	 * @throws TInvalidDataValueException if the key is empty
	 * @since 4.4.0
	 */
	public function setCookieValidationKey($value)
	{
		if ('' === $value) {
			throw new TInvalidDataValueException('securitymanager_cookievalidationkey_invalid');
		}

		$this->_cookieValidationKey = $value;
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
	 * @return string Hashing algorithm used to hash {@see getEncryptionKey} during {@see encrypt()} and {@see decrypt()}. Defaults to 'sha256'.
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
	 * @return bool Whether {@see encrypt()} prepends an HMAC over the IV+ciphertext block
	 * and {@see decrypt()} returns `false` for input without a valid HMAC. Defaults to `true`;
	 * Prado 4.3 defaulted to `false`.
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
	 * {@see decrypt()} honors a valid HMAC prefix with either setting. With `true`, input
	 * without a valid HMAC returns `false`; with `false`, it is decrypted as plain
	 * IV+ciphertext. Turning the property on makes data encrypted while it was off
	 * undecryptable.
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
			// The tests run with openssl.
			throw new TNotSupportedException('securitymanager_openssl_required'); // @codeCoverageIgnore
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
	 * The leading bytes are checked as an HMAC over the remainder, keyed on
	 * {@see getValidationKey ValidationKey} with {@see getHashAlgorithm HashAlgorithm}:
	 * - valid HMAC → the HMAC is stripped and the IV+ciphertext payload is decrypted.
	 * - no valid HMAC, {@see getUseEncryptionHmac UseEncryptionHmac} `true` → `false`.
	 * - no valid HMAC, UseEncryptionHmac `false` → the full input is decrypted as plain
	 *   IV+ciphertext.
	 *
	 * @param string $data data to be decrypted.
	 * @throws TNotSupportedException if PHP OpenSSL extension is not loaded
	 * @return false|string the decrypted data, or `false` on failure: a missing or
	 *   mismatched HMAC with UseEncryptionHmac `true`, or a cipher failure
	 */
	public function decrypt($data)
	{
		if (!extension_loaded('openssl')) {
			// The tests run with openssl.
			throw new TNotSupportedException('securitymanager_openssl_required'); // @codeCoverageIgnore
		}
		$key = hash($this->getEncryptionKeyAlgorithm(), $this->getEncryptionKey());
		$cryptAlgorithm = $this->getCryptAlgorithm();
		$ivLen = openssl_cipher_iv_length($cryptAlgorithm);

		// The HMAC is a raw digest of fixed length, so the digest of '' gives its length.
		// A valid HMAC prefix is honored with UseEncryptionHmac on or off; with it on, input
		// without one is rejected, never decrypted unauthenticated.
		// Native strlen() is used here intentionally.
		$hmacLen = strlen(hash_hmac($this->getHashAlgorithm(), '', $this->getValidationKey(), true));
		if ($this->strlen($data) > $hmacLen) {
			$storedHmac = $this->substr($data, 0, $hmacLen);
			$payload = $this->substr($data, $hmacLen);
			$expectedHmac = hash_hmac($this->getHashAlgorithm(), $payload, $this->getValidationKey(), true);
			if (hash_equals($expectedHmac, $storedHmac)) {
				$iv = $this->substr($payload, 0, $ivLen);
				return openssl_decrypt($this->substr($payload, $ivLen), $cryptAlgorithm, $key, 0, $iv);
			}
		}
		if ($this->getUseEncryptionHmac()) {
			return false;
		}

		// UseEncryptionHmac off: the full input is plain IV+ciphertext.
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
	 * @return false|string the real data with HMAC stripped off. False if the data
	 * is tampered.
	 */
	public function validateData($data)
	{
		return $this->validateHMAC($data, $this->computeHMAC(...));
	}

	/**
	 * Prefixes cookie data with an HMAC keyed by {@see getCookieValidationKey() CookieValidationKey}.
	 * @param string $data cookie data to be hashed.
	 * @return string data prefixed with HMAC
	 * @since 4.4.0
	 */
	public function hashCookieData($data)
	{
		return $this->computeCookieHMAC($data) . $data;
	}

	/**
	 * Validates cookie data generated by {@see hashCookieData()}.
	 * @param string $data cookie data to be validated.
	 * @return false|string the real data with HMAC stripped off. False if the data
	 * is tampered.
	 * @since 4.4.0
	 */
	public function validateCookieData($data)
	{
		return $this->validateHMAC($data, $this->computeCookieHMAC(...));
	}

	/**
	 * Strips and checks the HMAC prefix of data.
	 * @param string $data data prefixed with an HMAC.
	 * @param callable $computeHMAC computes the HMAC of a string.
	 * @return false|string the real data with HMAC stripped off. False if the data
	 * is tampered.
	 * @since 4.4.0
	 */
	private function validateHMAC($data, callable $computeHMAC)
	{
		$len = $this->strlen($computeHMAC('test'));

		if ($this->strlen($data) < $len) {
			return false;
		}

		$hmac = $this->substr($data, 0, $len);
		$data2 = $this->substr($data, $len);
		return hash_equals($computeHMAC($data2), $hmac) ? $data2 : false;
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
	 * Computes the HMAC for cookie data with {@see getCookieValidationKey CookieValidationKey}.
	 * @param string $data cookie data to be generated HMAC
	 * @return string the HMAC for the data
	 * @since 4.4.0
	 */
	protected function computeCookieHMAC($data)
	{
		return hash_hmac($this->getHashAlgorithm(), $data, $this->getCookieValidationKey());
	}

	/**
	 * Returns the hash algorithms `hash_hmac()` supports.
	 * @return array Array of supported hash methods, eg md5, sha1, sha256
	 * @since 4.3.3
	 */
	public function supportedHashAlgorithms()
	{
		return hash_hmac_algos();
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
	 * Returns the length of the given string in bytes.
	 * @param string $string the string being measured for length
	 * @return int the length of the string
	 */
	private function strlen($string)
	{
		return strlen($string);
	}

	/**
	 * Returns the portion of string specified by the start and optional length parameters, in bytes.
	 * When $length is omitted (null) the substring extends to the end of the string.
	 * @param string $string the input string. Must be one character or longer.
	 * @param int $start the starting position
	 * @param ?int $length the desired portion length, or null to read to the end
	 * @return string the extracted part of string, or FALSE on failure or an empty string.
	 */
	private function substr($string, $start, $length = null)
	{
		return substr($string, $start, $length);
	}

	/**
	 * Returns the nonce for a Content-Security-Policy, generated once per instance.
	 * @return string nonce
	 * @since 4.4.0
	 */
	public function getCSPNonce()
	{
		if ($this->_cspNonce === null) {
			$this->_cspNonce = $this->generateRandomKey();
		}
		return $this->_cspNonce;
	}
}
