<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TNotSupportedException;
use Prado\Security\TSecurityManager;
use Prado\TApplication;

class TSecurityManagerTest extends PHPUnit\Framework\TestCase
{
	public static $app;

	protected function setUp(): void
	{
		if (self::$app === null) {
			self::$app = new TApplication(__DIR__ . '/app');
		}
	}

	protected function tearDown(): void
	{
	}

	public function testInit()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		self::assertEquals($sec, self::$app->getSecurityManager());
	}

	public function testValidationKey()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		// Random validation key
		$valkey = $sec->getValidationKey();
		self::assertEquals($valkey, self::$app->getGlobalState(TSecurityManager::STATE_VALIDATION_KEY));

		$sec->setValidationKey('aKey');
		self::assertEquals('aKey', $sec->getValidationKey());

		try {
			$sec->setValidationKey('');
			self::fail('Expected TInvalidDataValueException not thrown');
		} catch (TInvalidDataValueException $e) {
		}
	}

	public function testEncryptionKey()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		// Random encryption key
		$valkey = $sec->getEncryptionKey();
		self::assertEquals($valkey, self::$app->getGlobalState(TSecurityManager::STATE_ENCRYPTION_KEY));

		$sec->setEncryptionKey('aKey');
		self::assertEquals('aKey', $sec->getEncryptionKey());

		try {
			$sec->setEncryptionKey('');
			self::fail('Expected TInvalidDataValueException not thrown');
		} catch (TInvalidDataValueException $e) {
		}
	}

	public function testValidation()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setHashAlgorithm('md5');
		self::assertEquals('md5', $sec->getHashAlgorithm());
		$sec->setHashAlgorithm('sha256');
		self::assertEquals('sha256', $sec->getHashAlgorithm());
		try {
			$sec->setHashAlgorithm('BAD');
			self::fail('Expected TInvalidDataValueException not thrown');
		} catch (TInvalidDataValueException $e) {
		}
	}

	public function testEncryption()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		try {
			$sec->setCryptAlgorithm('NotExisting');
			$foo = $sec->encrypt('dummy');
			self::fail('Expected TNotSupportedException not thrown');
		} catch (\Prado\Exceptions\TInvalidDataValueException $e) {
			self::assertEquals('NotExisting', $sec->getCryptAlgorithm());
		}
	}

	public function testEncryptDecrypt()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		// loop through different string size
		$testText = md5('a text (not) full of entrophy');
		for ($i = 1; $i < strlen($testText); $i++) {
			$sec->setEncryptionKey('aKey');
			$plainText = substr($testText, 0, $i);
			try {
				$encrypted = $sec->encrypt($plainText);
			} catch (TNotSupportedException $e) {
				self::markTestSkipped('openssl extension not loaded');
				return;
			}
			$decrypted = $sec->decrypt($encrypted);
			self::assertEquals($plainText, $decrypted);

			// try change key
			$sec->setEncryptionKey('anotherKey');
			self::assertNotEquals($plainText, $sec->decrypt($encrypted));
		}
	}


	public function testHashData()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setValidationKey('aKey');
		$sec->setHashAlgorithm('sha256');
		$hashed = $sec->hashData('A text to hash');
		// Lenght of sha256 hashed data must be 78 (64 + strlen data)
		self::assertEquals(78, strlen($hashed));
		// The initial text should be after the initial hash
		self::assertEquals('A text to hash', substr($hashed, 64));

		// Same tests with MD5
		$sec->setValidationKey('AnotherKey');
		$sec->setHashAlgorithm('md5');
		$hashed = $sec->hashData('A text to hash');
		// Lenght of md5 hashed data must be 46 (32 + strlen data)
		self::assertEquals(46, strlen($hashed));
		// The initial text should be after the initial hash
		self::assertEquals('A text to hash', substr($hashed, 32));
	}

	public function testValidateData()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setValidationKey('aKey');
		$sec->setHashAlgorithm('sha256');
		$hashed = $sec->hashData('A text to hash');
		self::assertEquals('A text to hash', $sec->validateData($hashed));
		// try to alter the hashed data
		$hashed[45] = "z";
		self::assertFalse($sec->validateData($hashed));
		// and a test without tampered data
		self::assertFalse($sec->validateData('bad'));
	}
}
