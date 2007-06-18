<?php
require_once dirname(__FILE__).'/../phpunit.php';

Prado::using('System.Security.TSecurityManager');

/**
 * @package System.Security
 */
class TSecurityManagerTest extends PHPUnit_Framework_TestCase {
	public static $app;

	public function setUp() {
		if (self::$app === null) {
			self::$app = new TApplication (dirname(__FILE__).'/app');
		}
	}

	public function tearDown() {
	}

	public function testInit() {
		$sec=new TSecurityManager ();
		$sec->init(null);
		self::assertEquals ($sec, self::$app->getSecurityManager());
	}
	
	public function testValidationKey() {
		$sec=new TSecurityManager ();
		$sec->init (null);
		// Random validation key
		$valkey=$sec->getValidationKey ();
		self::assertEquals($valkey, self::$app->getGlobalState(TSecurityManager::STATE_VALIDATION_KEY));
		
		$sec->setValidationKey ('aKey');
		self::assertEquals('aKey',$sec->getValidationKey());
		
		try {
			$sec->setValidationKey ('');
			self::fail ('Expected TInvalidDataValueException not thrown');
		} catch (TInvalidDataValueException $e) {}
	}
	
	public function testEncryptionKey() {
		$sec=new TSecurityManager ();
		$sec->init (null);
		// Random encryption key
		$valkey=$sec->getEncryptionKey ();
		self::assertEquals($valkey, self::$app->getGlobalState(TSecurityManager::STATE_ENCRYPTION_KEY));
		
		$sec->setEncryptionKey ('aKey');
		self::assertEquals('aKey',$sec->getEncryptionKey());
		
		try {
			$sec->setEncryptionKey ('');
			self::fail ('Expected TInvalidDataValueException not thrown');
		} catch (TInvalidDataValueException $e) {}
	}
	
	public function testValidation() {
		$sec=new TSecurityManager ();
		$sec->init (null);
		$sec->setValidation ('MD5');
		self::assertEquals('MD5',$sec->getValidation());
		$sec->setValidation ('SHA1');
		self::assertEquals('SHA1',$sec->getValidation());
		try {
			$sec->setValidation ('BAD');
			self::fail ('Expected TInvalidDataValueException not thrown');
		} catch (TInvalidDataValueException $e) {}
	}
	
	public function testEncryption() {
		$sec=new TSecurityManager ();
		$sec->init (null);
		try {
			$sec->setEncryption ('DES');
			self::fail ('Expected TNotSupportedException not thrown');
		} catch (TNotSupportedException $e) {
			self::assertEquals('3DES', $sec->getEncryption());
		}
	}
	
	public function testEncryptDecrypt() {
		$sec=new TSecurityManager ();
		$sec->init (null);
		$sec->setEncryptionKey ('aKey');
		try {
			$encrypted = $sec->encrypt('a text');
		} catch (TNotSupportedException $e) {
			self::markTestSkipped('mcrypt extension not loaded');
			return;
		}
		self::assertEquals('a text', $sec->decrypt($encrypted));
		// try change key 
		$sec->setEncryptionKey ('anotherKey');
		self::assertNotEquals('a text', $sec->decrypt($encrypted));
	}
	
	
	public function testHashData() {
		$sec=new TSecurityManager ();
		$sec->init (null);
		$sec->setValidationKey('aKey');
		$sec->setValidation('SHA1');
		$hashed=$sec->hashData('A text to hash');
		// Lenght of SHA1 hashed data must be 54 (40 + strlen data)
		self::assertEquals (54, strlen($hashed));
		// The initial text should be after the initial hash
		self::assertEquals ('A text to hash', substr($hashed,40));
		
		// Same tests with MD5
		$sec->setValidationKey('AnotherKey');
		$sec->setValidation('MD5');
		$hashed=$sec->hashData('A text to hash');
		// Lenght of SHA1 hashed data must be 46 (32 + strlen data)
		self::assertEquals (46, strlen($hashed));
		// The initial text should be after the initial hash
		self::assertEquals ('A text to hash', substr($hashed,32));
	}
	
	public function testValidateData() {
		$sec=new TSecurityManager ();
		$sec->init (null);
		$sec->setValidationKey('aKey');
		$sec->setValidation('SHA1');
		$hashed=$sec->hashData('A text to hash');
		self::assertEquals('A text to hash', $sec->validateData($hashed));
		// try to alter the hashed data
		$hashed[45]="z";
		self::assertFalse($sec->validateData($hashed));
		// and a test without tampered data
		self::assertFalse($sec->validateData('bad'));
	}
	

}

?>
