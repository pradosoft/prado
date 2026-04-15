<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TNotSupportedException;
use Prado\Security\TSecurityManager;
use Prado\TApplication;

class TCustomTestSecurityManager extends TSecurityManager
{
	public function publicGenerateRandomKey()
	{
		return $this->generateRandomKey();
	}
}

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
	
	public function testGenerateRandomKey()
	{
		$sec = new TCustomTestSecurityManager();
		$sec->init(null);
		$randomKey = $sec->publicGenerateRandomKey();
		
		self::assertIsString($randomKey);
		$this->assertSame(32, strlen($randomKey));
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
		$defaultAlgo = $sec->getCryptAlgorithm();
		try {
			$sec->setCryptAlgorithm('NotExisting');
			self::fail('Expected TInvalidDataValueException not thrown');
		} catch (\Prado\Exceptions\TInvalidDataValueException $e) {
			self::assertEquals($defaultAlgo, $sec->getCryptAlgorithm());
		}
		$sec->setCryptAlgorithm('aes-256-cbc');
		self::assertEquals('aes-256-cbc', $sec->getCryptAlgorithm());
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
	
	public function testSupportedHashAlgorithms()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		$algos = $sec->supportedHashAlgorithms();
		self::assertIsArray($algos);
		self::assertNotEmpty($algos);
		self::assertContains('md5', $algos);
		self::assertContains('sha256', $algos);
	}
	
	public function testSupportedCipherAlgorithms()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		$algos = $sec->supportedCipherAlgorithms();
		self::assertIsArray($algos);
		self::assertContains('aes-192-cbc', $algos);
		self::assertContains('aes-256-cbc', $algos);
	}

	public function testEncryptionKeyAlgorithm()
	{
		$sec = new TSecurityManager();
		$sec->init(null);

		self::assertEquals('md5', $sec->getEncryptionKeyAlgorithm());

		$sec->setEncryptionKeyAlgorithm('sha1');
		self::assertEquals('sha1', $sec->getEncryptionKeyAlgorithm());

		$sec->setEncryptionKeyAlgorithm('sha256');
		self::assertEquals('sha256', $sec->getEncryptionKeyAlgorithm());

		try {
			$sec->setEncryptionKeyAlgorithm('BADALGO');
			self::fail('Expected TInvalidDataValueException not thrown');
		} catch (TInvalidDataValueException $e) {
		}
	}

	public function testEncryptionKeyAlgorithmWithEncryptDecrypt()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('testEncryptionKey');

		foreach (['md5', 'sha1', 'sha256'] as $algo) {
			$sec->setEncryptionKeyAlgorithm($algo);
			self::assertEquals($algo, $sec->getEncryptionKeyAlgorithm());

			$plainText = 'Test data for encryption with ' . $algo;
			$encrypted = $sec->encrypt($plainText);
			$decrypted = $sec->decrypt($encrypted);
			self::assertEquals($plainText, $decrypted, "Failed with algorithm: $algo");
		}
	}

	public function testDifferentEncryptionKeyAlgorithmsProduceDifferentResults()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('sameKey');

		$plainText = 'Same plaintext';

		$sec->setEncryptionKeyAlgorithm('md5');
		$encryptedMd5 = $sec->encrypt($plainText);
		$decryptedMd5 = $sec->decrypt($encryptedMd5);
		self::assertEquals($plainText, $decryptedMd5);

		$sec->setEncryptionKeyAlgorithm('sha1');
		$encryptedSha1 = $sec->encrypt($plainText);
		$decryptedSha1 = $sec->decrypt($encryptedSha1);
		self::assertEquals($plainText, $decryptedSha1);

		$sec->setEncryptionKeyAlgorithm('sha256');
		$encryptedSha256 = $sec->encrypt($plainText);
		$decryptedSha256 = $sec->decrypt($encryptedSha256);
		self::assertEquals($plainText, $decryptedSha256);

		self::assertNotEquals($encryptedMd5, $encryptedSha1);
		self::assertNotEquals($encryptedSha1, $encryptedSha256);
		self::assertNotEquals($encryptedMd5, $encryptedSha256);

		$sec->setEncryptionKeyAlgorithm('sha1');
		$wrongDecrypt = $sec->decrypt($encryptedMd5);
		self::assertNotEquals($plainText, $wrongDecrypt);
	}

	public function testEncryptDecryptWithEmptyString()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');

		$encrypted = $sec->encrypt('');
		$decrypted = $sec->decrypt($encrypted);
		self::assertEquals('', $decrypted);
	}

	public function testEncryptDecryptWithBinaryData()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');

		$binaryData = "\x00\x01\x02\xff\xfe\xfd" . bin2hex(random_bytes(16));
		$encrypted = $sec->encrypt($binaryData);
		$decrypted = $sec->decrypt($encrypted);
		self::assertEquals($binaryData, $decrypted);
	}

	public function testEncryptDecryptWithSpecialCharacters()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');

		$specialChars = "!@#$%^&*()_+-=[]{}|;':\",./<>?\n\r\t\\";
		$encrypted = $sec->encrypt($specialChars);
		$decrypted = $sec->decrypt($encrypted);
		self::assertEquals($specialChars, $decrypted);
	}

	public function testEncryptDecryptWithLongData()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');

		$longData = str_repeat('A very long string to test encryption and decryption. ', 100);
		$encrypted = $sec->encrypt($longData);
		$decrypted = $sec->decrypt($encrypted);
		self::assertEquals($longData, $decrypted);
	}

	public function testEncryptDecryptWithDifferentKeysSameAlgorithm()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);

		$plainText = 'Same plaintext, different keys';

		$sec->setEncryptionKey('key1');
		$encrypted1 = $sec->encrypt($plainText);
		$decrypted1 = $sec->decrypt($encrypted1);
		self::assertEquals($plainText, $decrypted1);

		$sec->setEncryptionKey('key2');
		$encrypted2 = $sec->encrypt($plainText);
		$decrypted2 = $sec->decrypt($encrypted2);
		self::assertEquals($plainText, $decrypted2);

		self::assertNotEquals($encrypted1, $encrypted2);

		$sec->setEncryptionKey('key1');
		self::assertFalse($sec->decrypt($encrypted2));
	}

	public function testEncryptDecryptWithUtf8Data()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');

		$utf8Data = 'Unicode data: 你好世界 🌍 éèê ñ';
		$encrypted = $sec->encrypt($utf8Data);
		$decrypted = $sec->decrypt($encrypted);
		self::assertEquals($utf8Data, $decrypted);
	}

	public function testEncryptDecryptCipherMismatchFails()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');
		$sec->setCryptAlgorithm('aes-256-cbc');

		$plainText = 'Test data';
		$encrypted = $sec->encrypt($plainText);

		$sec->setCryptAlgorithm('aes-256-ecb');
		$decrypted = $sec->decrypt($encrypted);
		self::assertNotEquals($plainText, $decrypted);
	}

	public function testValidationKeyEmptyStringThrowsException()
	{
		$sec = new TSecurityManager();
		$sec->init(null);

		$this->expectException(TInvalidDataValueException::class);
		$sec->setValidationKey('');
	}

	public function testEncryptionKeyEmptyStringThrowsException()
	{
		$sec = new TSecurityManager();
		$sec->init(null);

		$this->expectException(TInvalidDataValueException::class);
		$sec->setEncryptionKey('');
	}

	public function testHashAlgorithmInvalidThrowsException()
	{
		$sec = new TSecurityManager();
		$sec->init(null);

		$this->expectException(TInvalidDataValueException::class);
		$sec->setHashAlgorithm('invalid_hash_algo');
	}

	public function testCryptAlgorithmInvalidThrowsException()
	{
		$sec = new TSecurityManager();
		$sec->init(null);

		$this->expectException(TInvalidDataValueException::class);
		$sec->setCryptAlgorithm('invalid_crypt_algo');
	}

	public function testEncryptionKeyAlgorithmInvalidThrowsException()
	{
		$sec = new TSecurityManager();
		$sec->init(null);

		$this->expectException(TInvalidDataValueException::class);
		$sec->setEncryptionKeyAlgorithm('invalid_hash_algo');
	}

	public function testValidateDataWithTamperedData()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setValidationKey('aKey');
		$sec->setHashAlgorithm('sha256');

		$hashed = $sec->hashData('Test data');
		$originalChar = $hashed[10];
		$hashed[10] = chr(ord($originalChar) + 1);
		self::assertFalse($sec->validateData($hashed));
	}

	public function testValidateDataWithShortData()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setValidationKey('aKey');
		$sec->setHashAlgorithm('sha256');

		self::assertFalse($sec->validateData('short'));
	}

	public function testHashDataValidationKeyPersistence()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setHashAlgorithm('sha256');

		$sec->setValidationKey('key1');
		$hashed1 = $sec->hashData('Test data');

		$sec->setValidationKey('key2');
		$hashed2 = $sec->hashData('Test data');

		self::assertNotEquals($hashed1, $hashed2);
	}

	public function testEncryptProducesDifferentOutputEachTime()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');

		$plainText = 'Same plaintext';

		$encrypted1 = $sec->encrypt($plainText);
		$encrypted2 = $sec->encrypt($plainText);

		self::assertNotEquals($encrypted1, $encrypted2);
		self::assertEquals($plainText, $sec->decrypt($encrypted1));
		self::assertEquals($plainText, $sec->decrypt($encrypted2));
	}

	public function testEncryptionKeyAlgorithmAffectsKeyDerivation()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('sharedkey');

		$plainText = 'Data to verify key derivation';

		$sec->setEncryptionKeyAlgorithm('md5');
		$encrypted1 = $sec->encrypt($plainText);

		$sec->setEncryptionKeyAlgorithm('sha1');
		$encrypted2 = $sec->encrypt($plainText);

		$sec->setEncryptionKeyAlgorithm('sha256');
		$encrypted3 = $sec->encrypt($plainText);

		self::assertNotEquals($encrypted1, $encrypted2);
		self::assertNotEquals($encrypted2, $encrypted3);
		self::assertNotEquals($encrypted1, $encrypted3);

		$sec->setEncryptionKeyAlgorithm('md5');
		self::assertEquals($plainText, $sec->decrypt($encrypted1));
		$sec->setEncryptionKeyAlgorithm('sha1');
		self::assertEquals($plainText, $sec->decrypt($encrypted2));
		$sec->setEncryptionKeyAlgorithm('sha256');
		self::assertEquals($plainText, $sec->decrypt($encrypted3));
	}

	public function testSingleByteEncryptionDecryption()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');

		for ($i = 0; $i < 256; $i++) {
			$char = chr($i);
			$encrypted = $sec->encrypt($char);
			$decrypted = $sec->decrypt($encrypted);
			self::assertEquals($char, $decrypted, "Failed for byte: $i");
		}
	}

	public function testGenerateRandomKeyUniqueness()
	{
		$sec = new TCustomTestSecurityManager();
		$sec->init(null);

		$keys = [];
		for ($i = 0; $i < 10; $i++) {
			$keys[] = $sec->publicGenerateRandomKey();
		}

		$uniqueKeys = array_unique($keys);
		self::assertCount(10, $uniqueKeys, 'Random keys should be unique');
	}

	public function testValidationKeyGlobalStatePersistence()
	{
		$sec = new TSecurityManager();
		$sec->init(null);

		$key1 = $sec->getValidationKey();
		$key2 = $sec->getValidationKey();

		self::assertEquals($key1, $key2);
		self::assertEquals($key1, self::$app->getGlobalState(TSecurityManager::STATE_VALIDATION_KEY));
	}

	public function testEncryptionKeyGlobalStatePersistence()
	{
		$sec = new TSecurityManager();
		$sec->init(null);

		$key1 = $sec->getEncryptionKey();
		$key2 = $sec->getEncryptionKey();

		self::assertEquals($key1, $key2);
		self::assertEquals($key1, self::$app->getGlobalState(TSecurityManager::STATE_ENCRYPTION_KEY));
	}

	public function testEncryptDecryptAllSupportedAlgorithms()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('testKey');
		$plainText = 'Testing all hash algorithms as encryption key algorithm';

		$hashAlgos = $sec->supportedHashAlgorithms();
		$testAlgos = array_intersect(['md5', 'sha1', 'sha256', 'sha512'], $hashAlgos);

		foreach ($testAlgos as $algo) {
			$sec->setEncryptionKeyAlgorithm($algo);
			$encrypted = $sec->encrypt($plainText);
			$decrypted = $sec->decrypt($encrypted);
			self::assertEquals($plainText, $decrypted, "Failed for hash algorithm: $algo");
		}
	}

	public function testEncryptDecryptWithChangedEncryptionKeyAlgorithmMidProcess()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('testKey');
		$sec->setEncryptionKeyAlgorithm('sha256');

		$plainText = 'Test data';
		$encrypted = $sec->encrypt($plainText);

		$sec->setEncryptionKeyAlgorithm('md5');
		$decryptedWrong = $sec->decrypt($encrypted);
		self::assertNotEquals($plainText, $decryptedWrong);

		$sec->setEncryptionKeyAlgorithm('sha256');
		$decryptedCorrect = $sec->decrypt($encrypted);
		self::assertEquals($plainText, $decryptedCorrect);
	}

	public function testHashDataAndValidateDataRoundTrip()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setValidationKey('testKey');
		$sec->setHashAlgorithm('sha256');

		$original = 'Data to hash and validate';
		$hashed = $sec->hashData($original);
		$validated = $sec->validateData($hashed);

		self::assertEquals($original, $validated);
	}

	public function testValidateDataWithDifferentHashAlgorithms()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setValidationKey('testKey');

		$testData = 'Testing data with different hash algorithms';

		foreach (['md5', 'sha1', 'sha256'] as $algo) {
			if (!in_array($algo, $sec->supportedHashAlgorithms())) {
				continue;
			}
			$sec->setHashAlgorithm($algo);
			$hashed = $sec->hashData($testData);
			$validated = $sec->validateData($hashed);
			self::assertEquals($testData, $validated, "Failed for hash algorithm: $algo");
		}
	}

	public function testEncryptDecryptZeroLengthData()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');

		$encrypted = $sec->encrypt('');
		self::assertNotEmpty($encrypted, 'Encrypted empty string should not be empty');
		$decrypted = $sec->decrypt($encrypted);
		self::assertEquals('', $decrypted);
	}

	public function testHashDataEmptyString()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setValidationKey('aKey');
		$sec->setHashAlgorithm('sha256');

		$hashed = $sec->hashData('');
		self::assertNotEmpty($hashed);
		self::assertEquals('', $sec->validateData($hashed));
	}

	public function testEncryptKeyWithVeryLongEncryptionKey()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey(str_repeat('a', 1000));

		$plainText = 'Test with very long key';
		$encrypted = $sec->encrypt($plainText);
		$decrypted = $sec->decrypt($encrypted);
		self::assertEquals($plainText, $decrypted);
	}

	public function testEncryptKeyWithSpecialCharactersInKey()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey("special!@#$%^&*()_+-=[]{}|;':\",./<>?");

		$plainText = 'Test with special characters in key';
		$encrypted = $sec->encrypt($plainText);
		$decrypted = $sec->decrypt($encrypted);
		self::assertEquals($plainText, $decrypted);
	}
}
