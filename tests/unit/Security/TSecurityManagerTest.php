<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TNotSupportedException;
use Prado\Security\TSecurityManager;

class TCustomTestSecurityManager extends TSecurityManager
{
	public function publicGenerateRandomKey()
	{
		return $this->generateRandomKey();
	}
}

class TSecurityManagerTest extends PHPUnit\Framework\TestCase
{
	protected ?TTestApplication $app = null;

	protected function setUp(): void
	{
		$this->app = new TTestApplication(__DIR__ . '/app');
	}

	protected function tearDown(): void
	{
		if ($this->app !== null) {
			$this->app->restoreApplication();
			$this->app = null;
		}
	}

	public function testInit()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		self::assertEquals($sec, $this->app->getSecurityManager());
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
		self::assertEquals($valkey, $this->app->getGlobalState(TSecurityManager::STATE_VALIDATION_KEY));

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
		self::assertEquals($valkey, $this->app->getGlobalState(TSecurityManager::STATE_ENCRYPTION_KEY));

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

		// AES-CBC is unauthenticated: decrypting with the wrong key produces garbage
		// bytes and then runs PKCS7 padding validation on them.  ~255/256 of the time
		// padding fails and openssl_decrypt() returns false (correct).  But ~1/256 of
		// the time the garbage bytes accidentally satisfy PKCS7 and a garbage string is
		// returned instead — causing assertFalse to flap.
		//
		// Fix: each encrypt() call draws a fresh random IV via openssl_random_pseudo_bytes(),
		// so every re-encryption produces a structurally independent ciphertext even with
		// the same key and plaintext.  The IV propagates through all CBC blocks, making the
		// PKCS7 padding check on the garbage decryption an independent ~1/256 event each
		// roll.  The key pair (key2 encrypts, key1 tries to decrypt) intentionally stays
		// fixed — that is the scenario under test.  We reroll until we land on a ciphertext
		// whose wrong-key decrypt returns false, which happens on the first try ~255/256 of
		// the time.  The cap of 100 rolls is purely defensive: exhausting it requires the
		// 1/256 padding fluke to occur 100 times in a row — P ≈ (1/256)^100, effectively 0.
		$wrongKeyResult = false;
		for ($roll = 0; $roll < 100; $roll++) {
			$sec->setEncryptionKey('key2');
			$freshCipher = $sec->encrypt($plainText); // new random IV each call
			$sec->setEncryptionKey('key1');
			$wrongKeyResult = $sec->decrypt($freshCipher);
			if ($wrongKeyResult === false) {
				break;
			}
			// Rare (~1/256): garbage bytes accidentally passed PKCS7 — reroll for a new IV.
		}
		self::assertFalse($wrongKeyResult, 'Decrypting with wrong key must return false (rerolled IV until a clean ciphertext was found).');
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
		self::assertEquals($key1, $this->app->getGlobalState(TSecurityManager::STATE_VALIDATION_KEY));
	}

	public function testEncryptionKeyGlobalStatePersistence()
	{
		$sec = new TSecurityManager();
		$sec->init(null);

		$key1 = $sec->getEncryptionKey();
		$key2 = $sec->getEncryptionKey();

		self::assertEquals($key1, $key2);
		self::assertEquals($key1, $this->app->getGlobalState(TSecurityManager::STATE_ENCRYPTION_KEY));
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

	// -------------------------------------------------------------------------
	// UseEncryptionHmac tests
	// -------------------------------------------------------------------------

	public function testUseEncryptionHmacDefaultFalse()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		self::assertFalse($sec->getUseEncryptionHmac());
	}

	public function testUseEncryptionHmacSetterGetter()
	{
		$sec = new TSecurityManager();
		$sec->init(null);

		$sec->setUseEncryptionHmac(true);
		self::assertTrue($sec->getUseEncryptionHmac());

		$sec->setUseEncryptionHmac(false);
		self::assertFalse($sec->getUseEncryptionHmac());
	}

	public function testEncryptDecryptWithHmacEnabled()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('hmacKey');
		$sec->setValidationKey('validationKey');
		$sec->setUseEncryptionHmac(true);

		$plainText = 'Authenticated plaintext';
		$encrypted = $sec->encrypt($plainText);
		$decrypted = $sec->decrypt($encrypted);
		self::assertEquals($plainText, $decrypted);
	}

	public function testHmacEncryptedPayloadIsLongerThanPlain()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');
		$sec->setValidationKey('validationKey');

		$plainText = 'length comparison';

		$sec->setUseEncryptionHmac(false);
		$plainEncrypted = $sec->encrypt($plainText);

		$sec->setUseEncryptionHmac(true);
		$hmacEncrypted = $sec->encrypt($plainText);

		// HMAC-wrapped output must be longer by exactly the HMAC digest size.
		$hmacLen = strlen(hash_hmac($sec->getHashAlgorithm(), '', $sec->getValidationKey(), true));
		self::assertEquals(strlen($plainEncrypted) + $hmacLen, strlen($hmacEncrypted));
	}

	/**
	 * Ciphertext produced with UseEncryptionHmac=false must still be decryptable
	 * after the property is switched to true (forward migration).
	 */
	public function testDecryptPlainCiphertextWhenHmacEnabled()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('migrateKey');
		$sec->setValidationKey('validationKey');

		$plainText = 'Data encrypted before HMAC was enabled';

		// Encrypt without HMAC (old behaviour).
		$sec->setUseEncryptionHmac(false);
		$encryptedWithoutHmac = $sec->encrypt($plainText);

		// Switch property to true — decrypt must still recover the plaintext.
		$sec->setUseEncryptionHmac(true);
		$decrypted = $sec->decrypt($encryptedWithoutHmac);
		self::assertEquals($plainText, $decrypted);
	}

	/**
	 * Ciphertext produced with UseEncryptionHmac=true must still be decryptable
	 * after the property is switched back to false (reverse migration).
	 */
	public function testDecryptHmacCiphertextWhenHmacDisabled()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('migrateKey');
		$sec->setValidationKey('validationKey');

		$plainText = 'Data encrypted with HMAC, decrypted without';

		// Encrypt with HMAC.
		$sec->setUseEncryptionHmac(true);
		$encryptedWithHmac = $sec->encrypt($plainText);

		// Switch property off — HMAC is still auto-detected and stripped.
		$sec->setUseEncryptionHmac(false);
		$decrypted = $sec->decrypt($encryptedWithHmac);
		self::assertEquals($plainText, $decrypted);
	}

	/**
	 * Tampering any byte of an HMAC-wrapped ciphertext must return false reliably
	 * (no PKCS7 padding luck required).
	 */
	public function testHmacDetectsTampering()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');
		$sec->setValidationKey('validationKey');
		$sec->setUseEncryptionHmac(true);

		$plainText = 'Data that must not be tampered';
		$encrypted = $sec->encrypt($plainText);

		// Flip a byte in the ciphertext portion (well past the HMAC prefix).
		$tampered = $encrypted;
		$tampered[strlen($encrypted) - 1] = chr(ord($tampered[strlen($encrypted) - 1]) ^ 0xFF);

		// HMAC verification must fail deterministically — no 1/256 flap.
		self::assertFalse($sec->decrypt($tampered));
	}

	/**
	 * Tampering the HMAC prefix itself must also return false.
	 */
	public function testHmacPrefixTamperingReturnsFalse()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');
		$sec->setValidationKey('validationKey');
		$sec->setUseEncryptionHmac(true);

		$plainText = 'HMAC prefix tamper test';
		$encrypted = $sec->encrypt($plainText);

		// Corrupt the very first byte of the HMAC.
		$tampered = $encrypted;
		$tampered[0] = chr(ord($tampered[0]) ^ 0xFF);

		self::assertFalse($sec->decrypt($tampered));
	}

	/**
	 * With HMAC enabled, decrypting with the wrong encryption key must not return
	 * the original plaintext.  The HMAC itself passes (it covers the ciphertext, not
	 * the key), so the return value is garbage rather than false — which is fine; the
	 * important invariant is that the plaintext is not recovered.
	 */
	public function testHmacWithWrongEncryptionKeyDoesNotRecoverPlaintext()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setValidationKey('sharedValidationKey');
		$sec->setUseEncryptionHmac(true);

		$plainText = 'Secret text';

		$sec->setEncryptionKey('correctKey');
		$encrypted = $sec->encrypt($plainText);

		$sec->setEncryptionKey('wrongKey');
		$result = $sec->decrypt($encrypted);

		// The plaintext must not be recovered regardless of PKCS7 luck.
		self::assertNotEquals($plainText, $result);
	}

	public function testHmacRoundTripWithAllHashAlgorithms()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');
		$sec->setValidationKey('validationKey');
		$sec->setUseEncryptionHmac(true);

		$plainText = 'HMAC round-trip across hash algorithms';

		foreach (['md5', 'sha1', 'sha256'] as $algo) {
			$sec->setHashAlgorithm($algo);
			$encrypted = $sec->encrypt($plainText);
			$decrypted = $sec->decrypt($encrypted);
			self::assertEquals($plainText, $decrypted, "HMAC round-trip failed for hash algorithm: $algo");
		}
	}

	// -------------------------------------------------------------------------
	// Default value assertions
	// -------------------------------------------------------------------------

	public function testHashAlgorithmDefault()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		self::assertEquals('sha256', $sec->getHashAlgorithm());
	}

	public function testCryptAlgorithmDefault()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		self::assertEquals('aes-256-cbc', $sec->getCryptAlgorithm());
	}

	// -------------------------------------------------------------------------
	// generateRandomKey format
	// -------------------------------------------------------------------------

	public function testGenerateRandomKeyIsHexadecimal()
	{
		$sec = new TCustomTestSecurityManager();
		$sec->init(null);
		$key = $sec->publicGenerateRandomKey();
		self::assertMatchesRegularExpression('/^[0-9a-f]+$/', $key, 'Random key must be a lowercase hexadecimal string');
	}

	// -------------------------------------------------------------------------
	// getValidationKey / getEncryptionKey: global-state load branch
	// (null _key, global state already populated by a prior instance)
	// -------------------------------------------------------------------------

	public function testValidationKeyLoadedFromGlobalStateByNewInstance()
	{
		// First instance — generates and persists the key.
		$sec1 = new TSecurityManager();
		$sec1->init(null);
		$key1 = $sec1->getValidationKey();

		// Second instance — _validationKey starts null, but global state exists.
		$sec2 = new TSecurityManager();
		$sec2->init(null);
		$key2 = $sec2->getValidationKey();

		self::assertEquals($key1, $key2, 'New instance must load validation key from global state');
	}

	public function testEncryptionKeyLoadedFromGlobalStateByNewInstance()
	{
		$sec1 = new TSecurityManager();
		$sec1->init(null);
		$key1 = $sec1->getEncryptionKey();

		$sec2 = new TSecurityManager();
		$sec2->init(null);
		$key2 = $sec2->getEncryptionKey();

		self::assertEquals($key1, $key2, 'New instance must load encryption key from global state');
	}

	// -------------------------------------------------------------------------
	// decrypt() edge cases
	// -------------------------------------------------------------------------

	/**
	 * An empty string has no IV — openssl_decrypt must return false.
	 */
	public function testDecryptEmptyInput()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');

		self::assertFalse($sec->decrypt(''));
	}

	/**
	 * Data exactly $hmacLen bytes long hits the `> $hmacLen` boundary: the HMAC
	 * probe is skipped (condition is strictly greater-than), so the full blob is
	 * treated as a plain IV+ciphertext, which cannot be valid.
	 */
	public function testDecryptDataAtExactlyHmacLengthBoundarySkipsHmacPath()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');
		$sec->setValidationKey('validationKey');

		// Build a blob that is exactly $hmacLen bytes so the HMAC probe is skipped.
		// The bytes are crafted to equal the real HMAC of '' — if the probe ran, it
		// would produce a false positive.  Since the probe is bypassed, the blob is
		// handed to plain CBC decrypt as-is and cannot return our known plaintext.
		$hmacLen = strlen(hash_hmac($sec->getHashAlgorithm(), '', $sec->getValidationKey(), true));
		$blob = hash_hmac($sec->getHashAlgorithm(), '', $sec->getValidationKey(), true);
		self::assertEquals($hmacLen, strlen($blob)); // confirm the probe boundary

		$result = $sec->decrypt($blob);
		// Result is false or garbage — definitely not a plaintext we encrypted.
		self::assertNotEquals('any plaintext', $result);
	}

	// -------------------------------------------------------------------------
	// validateData() boundary
	// -------------------------------------------------------------------------

	/**
	 * Data whose length equals exactly the HMAC output length passes the
	 * `< $len` guard but the "data" portion after stripping the HMAC is empty;
	 * HMAC of '' will not match, so false is returned.
	 */
	public function testValidateDataExactlyHmacLength()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setValidationKey('aKey');
		$sec->setHashAlgorithm('sha256');

		// sha256 hex HMAC = 64 chars.  Feed exactly 64 chars of arbitrary data.
		$hmacLengthBlob = str_repeat('a', 64);
		self::assertFalse($sec->validateData($hmacLengthBlob));
	}

	/**
	 * Data one byte shorter than the HMAC length must also return false (it hits
	 * the `< $len` branch).
	 */
	public function testValidateDataOneByteShorterThanHmacLength()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setValidationKey('aKey');
		$sec->setHashAlgorithm('sha256');

		// sha256 hex HMAC = 64 chars.
		self::assertFalse($sec->validateData(str_repeat('a', 63)));
	}

	/**
	 * Data one byte longer than the HMAC length proceeds to comparison and fails
	 * (the trailing byte does not form a valid HMAC).
	 */
	public function testValidateDataOneByteLongerThanHmacLengthReturnsFalse()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setValidationKey('aKey');
		$sec->setHashAlgorithm('sha256');

		// sha256 hex HMAC = 64 chars.  65 bytes with junk HMAC prefix.
		self::assertFalse($sec->validateData(str_repeat('a', 65)));
	}

	// -------------------------------------------------------------------------
	// UseEncryptionHmac — remaining edge cases
	// -------------------------------------------------------------------------

	public function testUseEncryptionHmacWithEmptyPlaintext()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');
		$sec->setValidationKey('validationKey');
		$sec->setUseEncryptionHmac(true);

		$encrypted = $sec->encrypt('');
		self::assertEquals('', $sec->decrypt($encrypted));
	}

	public function testUseEncryptionHmacWithBinaryData()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');
		$sec->setValidationKey('validationKey');
		$sec->setUseEncryptionHmac(true);

		$binary = "\x00\x01\x02\xff\xfe\xfd" . bin2hex(random_bytes(16));
		$encrypted = $sec->encrypt($binary);
		self::assertEquals($binary, $sec->decrypt($encrypted));
	}

	/**
	 * Tampering the byte immediately after the HMAC prefix (the first IV byte)
	 * must be caught by HMAC verification — not PKCS7 luck.
	 */
	public function testUseEncryptionHmacIvRegionTampering()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');
		$sec->setValidationKey('validationKey');
		$sec->setUseEncryptionHmac(true);

		$encrypted = $sec->encrypt('IV region tamper test');
		$hmacLen = strlen(hash_hmac($sec->getHashAlgorithm(), '', $sec->getValidationKey(), true));

		$tampered = $encrypted;
		$tampered[$hmacLen] = chr(ord($tampered[$hmacLen]) ^ 0xFF); // first IV byte

		self::assertFalse($sec->decrypt($tampered));
	}

	/**
	 * Changing the ValidationKey after HMAC-encrypt causes the HMAC probe to fail
	 * during decrypt.  The fallback plain path then treats the HMAC prefix bytes
	 * as part of the IV, so the recovered bytes are garbage — not the original text.
	 */
	public function testUseEncryptionHmacWithChangedValidationKeyProducesGarbage()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('encKey');
		$sec->setValidationKey('originalKey');
		$sec->setUseEncryptionHmac(true);

		$plainText = 'Secret data';
		$encrypted = $sec->encrypt($plainText);

		$sec->setValidationKey('differentKey');
		self::assertNotEquals($plainText, $sec->decrypt($encrypted));
	}

	/**
	 * Changing HashAlgorithm between HMAC-encrypt and decrypt alters both the
	 * expected HMAC length and its value.  The probe fails, decrypt falls back to
	 * the plain path with the HMAC bytes acting as a mangled IV, returning garbage.
	 */
	public function testUseEncryptionHmacWithChangedHashAlgorithmOnDecrypt()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');
		$sec->setValidationKey('validationKey');
		$sec->setUseEncryptionHmac(true);
		$sec->setHashAlgorithm('sha256');

		$plainText = 'Hash algorithm mismatch';
		$encrypted = $sec->encrypt($plainText);

		// Switch to md5 — different HMAC length (16 raw bytes vs 32) and value.
		$sec->setHashAlgorithm('md5');
		self::assertNotEquals($plainText, $sec->decrypt($encrypted));
	}

	/**
	 * Using HMAC mode with multiple supported cipher algorithms verifies that the
	 * varying IV lengths are handled correctly in both encrypt and decrypt.
	 */
	public function testUseEncryptionHmacWithDifferentCipherAlgorithms()
	{
		if (!extension_loaded('openssl')) {
			self::markTestSkipped('openssl extension not loaded');
		}

		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setEncryptionKey('aKey');
		$sec->setValidationKey('validationKey');
		$sec->setUseEncryptionHmac(true);

		$plainText = 'Cross-cipher HMAC test';

		foreach (['aes-128-cbc', 'aes-192-cbc', 'aes-256-cbc'] as $cipher) {
			if (!in_array($cipher, $sec->supportedCipherAlgorithms())) {
				continue;
			}
			$sec->setCryptAlgorithm($cipher);
			$encrypted = $sec->encrypt($plainText);
			$decrypted = $sec->decrypt($encrypted);
			self::assertEquals($plainText, $decrypted, "HMAC round-trip failed for cipher: $cipher");
		}
	}

	/**
	 * hashData() / validateData() must survive binary input that contains null
	 * bytes, high-value bytes, and sequences that could confuse mb_strlen.
	 */
	public function testHashDataValidateDataWithBinaryContent()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setValidationKey('validationKey');
		$sec->setHashAlgorithm('sha256');

		$binary = "\x00\x01\x7f\x80\xff" . str_repeat("\xfe\xfd", 10);
		$hashed = $sec->hashData($binary);
		self::assertEquals($binary, $sec->validateData($hashed));

		// Tamper one byte in the binary data section.
		$tampered = $hashed;
		$tampered[strlen($hashed) - 1] = chr(ord($tampered[strlen($hashed) - 1]) ^ 0x01);
		self::assertFalse($sec->validateData($tampered));
	}

	/**
	 * hashData() / validateData() must work identically regardless of whether
	 * mbstring is available (the _mbstring flag controls which strlen/substr is
	 * used internally; both must count binary octets, not characters).
	 *
	 * We verify this by running a UTF-8 string through hashData/validateData and
	 * checking that the HMAC prefix length and data recovery are byte-accurate.
	 */
	public function testHashDataValidateDataByteAccuracyWithMultibyteString()
	{
		$sec = new TSecurityManager();
		$sec->init(null);
		$sec->setValidationKey('aKey');
		$sec->setHashAlgorithm('sha256');

		// Multi-byte UTF-8 string — mb_strlen would give fewer chars than bytes.
		$data = 'こんにちは世界'; // 7 chars, 21 UTF-8 bytes
		$hashed = $sec->hashData($data);

		// sha256 HMAC hex = 64 chars; total must be 64 + 21 bytes.
		self::assertEquals(64 + strlen($data), strlen($hashed));
		self::assertEquals($data, $sec->validateData($hashed));
	}
}
