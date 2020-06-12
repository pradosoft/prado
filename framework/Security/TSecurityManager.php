<?php
/**
 * TSecurityManager class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Security
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
 * such as viewstate persister, cookies.
 *
 * TSecurityManager is mainly used to protect data from being tampered
 * and viewed. It can generate HMAC and encrypt the data.
 * The private key used to generate HMAC is set by {@link setValidationKey ValidationKey}.
 * The key used to encrypt data is specified by {@link setEncryptionKey EncryptionKey}.
 * If the above keys are not explicitly set, random keys will be generated
 * and used.
 *
 * To prefix data with an HMAC, call {@link hashData()}.
 * To validate if data is tampered, call {@link validateData()}, which will
 * return the real data if it is not tampered.
 * The algorithm used to generated HMAC is specified by {@link setHashAlgorithm HashAlgorithm}.
 *
 * To encrypt and decrypt data, call {@link encrypt()} and {@link decrypt()}
 * respectively. The encryption algorithm can be set by {@link setCryptAlgorithm CryptAlgorithm}.
 *
 * Note, to use encryption, the PHP OpenSSL extension must be loaded. This was introduced in
 * Prado4, older versions used the deprecated mcrypt extension with rijndael-256 cipher as
 * default, which does not have an equivalent in OpenSSL. Developers should keep that in mind
 * when migrating from Prado3 to Prado4.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author LANDWEHR Computer und Software GmbH <programmierung@landwehr-software.de>
 * @package Prado\Security
 * @since 3.0
 */
class TSecurityManager extends \Prado\TModule
{
	const STATE_VALIDATION_KEY = 'prado:securitymanager:validationkey';
	const STATE_ENCRYPTION_KEY = 'prado:securitymanager:encryptionkey';

	private $_validationKey;
	private $_encryptionKey;
	private $_hashAlgorithm = 'sha256';
	private $_cryptAlgorithm = 'aes-256-cbc';
	private $_mbstring;

	/**
	 * Initializes the module.
	 * The security module is registered with the application.
	 * @param TXmlElement $config initial module configuration
	 */
	public function init($config)
	{
		$this->_mbstring = extension_loaded('mbstring');
		$this->getApplication()->setSecurityManager($this);
	}

	/**
	 * Generates a random key.
	 */
	protected function generateRandomKey()
	{
		return sprintf('%08x%08x%08x%08x', mt_rand(), mt_rand(), mt_rand(), mt_rand());
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
			if (null === ($this->_encryptionKey = $this->getApplication()->getGlobalState(self::STATE_ENCRYPTION_KEY))) {
				$this->_encryptionKey = $this->generateRandomKey();
				$this->getApplication()->setGlobalState(self::STATE_ENCRYPTION_KEY, $this->_encryptionKey, null, true);
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
	 * @return string hashing algorithm used to generate HMAC. Defaults to 'sha256'.
	 */
	public function getHashAlgorithm()
	{
		return $this->_hashAlgorithm;
	}

	/**
	 * This method accepts all hash algorithms returned by hash_algos().
	 * @param string $value hashing algorithm used to generate HMAC.
	 * @throws TInvalidDataValueException if the hash algorithm is not supported.
	 */
	public function setHashAlgorithm($value)
	{
		$this->_hashAlgorithm = TPropertyValue::ensureString($value);
		if (!in_array($this->_hashAlgorithm, hash_algos())) {
			throw new TInvalidDataValueException('securitymanager_hash_algorithm_invalid');
		}
	}

	/**
	 * @return mixed the algorithm used to encrypt/decrypt data. Defaults to the string 'aes-256-cbc'.
	 */
	public function getCryptAlgorithm()
	{
		return $this->_cryptAlgorithm;
	}

	/**
	 * Sets the crypt algorithm (also known as cipher or cypher) that will be used for {@link encrypt} and {@link decrypt}.
	 * @param mixed $value either a string containing the cipther name.
	 */
	public function setCryptAlgorithm($value)
	{
		$this->_cryptAlgorithm = TPropertyValue::ensureString($value);
		if (!in_array($this->_cryptAlgorithm, openssl_get_cipher_methods())) {
			throw new TInvalidDataValueException('securitymanager_crypt_algorithm_invalid');
		}
	}

	/**
	 * Encrypts data with {@link getEncryptionKey EncryptionKey}.
	 * @param string $data data to be encrypted.
	 * @throws TNotSupportedException if PHP OpenSSL extension is not loaded
	 * @return string the encrypted data
	 */
	public function encrypt($data)
	{
		if (extension_loaded('openssl')) {
			$key = md5($this->getEncryptionKey());
			$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->_cryptAlgorithm));
			return $iv . openssl_encrypt($data, $this->_cryptAlgorithm, $key, null, $iv);
		} else {
			throw new TNotSupportedException('securitymanager_openssl_required');
		}
	}

	/**
	 * Decrypts data with {@link getEncryptionKey EncryptionKey}.
	 * @param string $data data to be decrypted.
	 * @throws TNotSupportedException if PHP OpenSSL extension is not loaded
	 * @return string the decrypted data
	 */
	public function decrypt($data)
	{
		if (extension_loaded('openssl')) {
			$key = md5($this->getEncryptionKey());
			$iv = $this->substr($data, 0, openssl_cipher_iv_length($this->_cryptAlgorithm));
			return openssl_decrypt($this->substr($data, $this->strlen($iv), $this->strlen($data)), $this->_cryptAlgorithm, $key, null, $iv);
		} else {
			throw new TNotSupportedException('securitymanager_openssl_required');
		}
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
	 * generated using {@link hashData()}.
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
		$data2 = $this->substr($data, $len, $this->strlen($data));
		return $hmac === $this->computeHMAC($data2) ? $data2 : false;
	}

	/**
	 * Computes the HMAC for the data with {@link getValidationKey ValidationKey}.
	 * @param string $data data to be generated HMAC
	 * @return string the HMAC for the data
	 */
	protected function computeHMAC($data)
	{
		return hash_hmac($this->_hashAlgorithm, $data, $this->getValidationKey());
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
	 * Returns the portion of string specified by the start and length parameters.
	 * If available uses the multibyte string function mb_substr
	 * @param string $string the input string. Must be one character or longer.
	 * @param int $start the starting position
	 * @param int $length the desired portion length
	 * @return string the extracted part of string, or FALSE on failure or an empty string.
	 */
	private function substr($string, $start, $length)
	{
		return $this->_mbstring ? mb_substr($string, $start, $length, '8bit') : substr($string, $start, $length);
	}
}
