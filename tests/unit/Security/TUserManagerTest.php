<?php

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\Security\TUser;
use Prado\Security\TUserManager;
use Prado\TApplication;
use Prado\Xml\TXmlDocument;

class CustomTestHashBehavior extends TBehavior {
	
	public function dyHasHash($value, $passwordMode, $callchain)
	{
		if (strtolower($passwordMode) == 'reverse' || strtolower($passwordMode) == 'esrever') {
			$value |= true;
		}
		return $callchain->dyHasHash($value, $passwordMode);
	}
	
	public function dyHash($password, $passwordMode, $callchain)
	{
		if (strtolower($passwordMode) == 'reverse' || strtolower($passwordMode) == 'esrever') {
			$password = strrev($password);
		}
		return $callchain->dyHash($password, $passwordMode);
	}
}


class TUserManagerTest extends PHPUnit\Framework\TestCase
{
	public static $app = null;
	public static $configXml = null;
	public static $configPhp = null;

	protected function setUp(): void
	{
		// Config type might change per test and the property must be fresh to change (null)
		self::$app = new TApplication(__DIR__ . '/app');
		prado::setPathofAlias('App', __DIR__);
		
		if (self::$configXml === null) {
			// Simulate a config file
			self::$configXml = new TXmlDocument('1.0', 'utf8');
			self::$configXml->loadFromString('<users><user name="Joe" password="demo"/><user name="John" password="demo" /><user name="test" password="test" roles="Reader, User"/><role name="Administrator" users="John" /><role name="Writer" users="Joe, John" /></users>');
		}
		
		if (self::$configPhp === null) {
			// Simulate a config file
			self::$configPhp = [
				'users' => [['name' => 'Mary', 'password' => 'mpass'], 
							['name' => 'Amy', 'password' => 'apass'], 
							['name' => 'Pamela', 'password' => 'ppass', 'roles' => 'Boss, User']],
				
				'roles' => [['name' => 'Supervisor', 'users' => 'Amy'],
							['name' => 'Author', 'users' => 'Mary, Pamela']],
			];
		}
	}

	protected function tearDown(): void
	{
	}
	
	public function testInitXml()
	{
		$userManager = new TUserManager();
		$userManager->init(self::$configXml);
		self::assertEquals(['joe' => 'demo', 'john' => 'demo', 'test' => 'test'], $userManager->getUsers());
		$userManager = null;
		
		// Test with a file
		if (is_writable(__DIR__)) {
			$xmlPath = __DIR__ . '/users.xml';
			self::$configXml->saveToFile($xmlPath);
			$userManager = new TUserManager();
			$userManager->setUserFile('App.users');
			$userManager->init(new TXmlDocument()); // Empty config
			self::assertEquals($xmlPath, $userManager->getUserFile());
			self::assertEquals(['joe' => 'demo', 'john' => 'demo', 'test' => 'test'], $userManager->getUsers());
			unlink($xmlPath);
		}
	}
	
	public function testInitPhp()
	{
		$appMode = self::$app->getConfigurationType();
		self::$app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		
		{
			$userManager = new TUserManager();
			$userManager->init(self::$configPhp);
			self::assertEquals(['mary' => 'mpass', 'amy' => 'apass', 'pamela' => 'ppass'], $userManager->getUsers());
			$userManager = null;
			
			// Test with a file
			if (is_writable(__DIR__)) {
				$phpPath = __DIR__ . '/users.php';
				$writeBytes = file_put_contents($phpPath, $fileContents = "<?php\nreturn " . var_export(self::$configPhp, true) . ";\n");
				self::assertEquals($fileContents, file_get_contents($phpPath));
				self::assertEquals(TApplication::CONFIG_TYPE_PHP, self::$app->getConfigurationType());
				self::assertEquals(TApplication::CONFIG_FILE_EXT_PHP, self::$app->getConfigurationFileExt());
				if ($writeBytes !== false) {
					$userManager = new TUserManager();
					$userManager->setUserFile('App.users');
					$userManager->init([]); // Empty config
					self::assertEquals($phpPath, $userManager->getUserFile());
					self::assertEquals(['mary' => 'mpass', 'amy' => 'apass', 'pamela' => 'ppass'], $userManager->getUsers());
					unlink($phpPath);
				}
			}
		}
		
		self::$app->setConfigurationType($appMode);
	}
	
	public function testUsersXml()
	{
		$userManager = new TUserManager();
		$userManager->init(self::$configXml);
		self::assertEquals(['joe' => 'demo', 'john' => 'demo', 'test' => 'test'], $userManager->getUsers());
	}
	
	public function testUsersPhp()
	{
		$appMode = self::$app->getConfigurationType();
		self::$app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		
		{
			$userManager = new TUserManager();
			$userManager->init(self::$configPhp);
			self::assertEquals(['mary' => 'mpass', 'amy' => 'apass', 'pamela' => 'ppass'], $userManager->getUsers());
		}
		
		self::$app->setConfigurationType($appMode);
	}
	
	public function testRolesXml()
	{
		$userManager = new TUserManager();
		$userManager->init(self::$configXml);
		self::assertEquals(['joe' => ['Writer'], 'john' => ['Administrator', 'Writer'], 'test' => ['Reader', 'User']], $userManager->getRoles());
	}
	
	public function testRolesPhp()
	{
		$appMode = self::$app->getConfigurationType();
		self::$app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		
		{
			$userManager = new TUserManager();
			$userManager->init(self::$configPhp);
			self::assertEquals(['mary' => ['Author'], 'amy' => ['Supervisor'], 'pamela' => ['Boss', 'User', 'Author']], $userManager->getRoles());
		}
		
		self::$app->setConfigurationType($appMode);
	}

	public function testUserFileEdgeCases()
	{
		$userManager = new TUserManager();
		try {
			$userManager->setUserFile('invalidFile');
			self::fail('Exception TConfigurationException not thrown');
		} catch (TConfigurationException $e) {
			self::assertTrue(true); // if didn't fail, still succeeds
		}
	}

	public function testGuestName()
	{
		$userManager = new TUserManager();
		self::assertEquals('Guest', $userManager->getGuestName());
		$userManager->setGuestName('Invite');
		self::assertEquals('Invite', $userManager->getGuestName());
	}

	public function testPasswordMode()
	{
		$userManager = new TUserManager();
		$userManager->setPasswordMode('Clear');
		self::assertEquals(TUserManagerPasswordMode::Clear, $userManager->getPasswordMode());
		$userManager->setPasswordMode('MD5');
		self::assertEquals(TUserManagerPasswordMode::MD5, $userManager->getPasswordMode());
		$userManager->setPasswordMode('SHA1');
		self::assertEquals(TUserManagerPasswordMode::SHA1, $userManager->getPasswordMode());
		
		
		foreach(hash_algos() as $algorithm) {
			$userManager->setPasswordMode($algorithm);
			self::assertEquals($algorithm, $userManager->getPasswordMode());
		}
		
		try {
			$userManager->setPasswordMode('Invalid');
			self::fail('Exception TInvalidDataValueException not thrown');
		} catch (TInvalidDataValueException $e) {
		}
	}
	
	public function testGetUserClass()
	{
		$userManager = new TUserManager();
		$userManager->init(self::$configXml);
		
		self::assertEquals(TUser::class, $userManager->getUserClass());
	}

	public function testValidateUser()
	{
		$userManager = new TUserManager();
		$userManager->init(self::$configXml);
		$userManager->setPasswordMode('Clear');
		
		self::assertTrue($userManager->validateUser('Joe', 'demo'));
		self::assertTrue($userManager->validateUser('joe', 'demo'));
		self::assertFalse($userManager->validateUser('Joe', 'incorrect'));
		self::assertFalse($userManager->validateUser('John', 'bad'));
	}
	
	public function testPasswordMode_Clear()
	{
		$appMode = self::$app->getConfigurationType();
		self::$app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		
		$config = self::$configPhp;
		$password = $config['users'][0]['password'];
		
		$userManager = new TUserManager();
		$userManager->init($config);
		$userManager->setPasswordMode('Clear');
		
		self::assertTrue($userManager->validateUser('Mary', $password));
		self::assertTrue($userManager->validateUser('mary', $password));
		self::assertFalse($userManager->validateUser('Mary', $password . '-'));
		self::assertFalse($userManager->validateUser('Amy', $password));
	}
	
	public function testPasswordMode_MD5()
	{
		$appMode = self::$app->getConfigurationType();
		self::$app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
			
		$config = self::$configPhp;
		$password = $config['users'][0]['password'];
		$config['users'][0]['password'] = md5($password);
		
		$userManager = new TUserManager();
		$userManager->init($config);
		$userManager->setPasswordMode('MD5');
		
		self::assertTrue($userManager->validateUser('Mary', $password));
		self::assertTrue($userManager->validateUser('mary', $password));
		self::assertFalse($userManager->validateUser('Mary', $password . '-'));
		self::assertFalse($userManager->validateUser('Amy', $password));
	}
	
	public function testPasswordMode_SHA1()
	{
		$appMode = self::$app->getConfigurationType();
		self::$app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
			
		$config = self::$configPhp;
		$password = $config['users'][0]['password'];
		$config['users'][0]['password'] = sha1($password);
		
		$userManager = new TUserManager();
		$userManager->init($config);
		$userManager->setPasswordMode('SHA1');
		
		self::assertTrue($userManager->validateUser('Mary', $password));
		self::assertTrue($userManager->validateUser('mary', $password));
		self::assertFalse($userManager->validateUser('Mary', $password . '-'));
		self::assertFalse($userManager->validateUser('Amy', $password));
	}
	
	public function testPasswordMode_Alt()
	{
		$appMode = self::$app->getConfigurationType();
		self::$app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		
		foreach(hash_algos() as $algorithm) {
			$config = self::$configPhp;
			$password = $config['users'][0]['password'];
			$config['users'][0]['password'] = hash($algorithm, $password);
			
			$userManager = new TUserManager();
			$userManager->init($config);
			$userManager->setPasswordMode($algorithm);
			
			self::assertTrue($userManager->validateUser('Mary', $password));
			self::assertTrue($userManager->validateUser('mary', $password));
			self::assertFalse($userManager->validateUser('Mary', $password . '-'));
			self::assertFalse($userManager->validateUser('Amy', $password));
		}
	}
	
	public function testGetUser()
	{
		$userManager = new TUserManager();
		$userManager->init(self::$configXml);
		
		$count = 0;
		$eventSender = $eventParam = null;
		$userManager->onFinalizeUser[] = function($sender, $param) use (&$count, &$eventSender, &$eventParam) {
			$count++;
			$eventSender = $sender;
			$eventParam = $param;
		};
			
		$guest = $userManager->getUser(null);
		self::assertEquals(1, $count);
		self::assertEquals($userManager, $eventSender);
		self::assertEquals($guest, $eventParam);
		self::assertInstanceOf(\Prado\Security\TUser::class, $guest);
		self::assertTrue($guest->getIsGuest());
		
		$eventSender = $eventParam = null;
		$user = $userManager->getUser('joe');
		self::assertEquals(2, $count);
		self::assertEquals($userManager, $eventSender);
		self::assertEquals($user, $eventParam);
		self::assertInstanceOf(\Prado\Security\TUser::class, $user);
		self::assertEquals('joe', $user->getName());
		self::assertEquals(['Writer'], $user->getRoles());
		self::assertFalse($user->getIsGuest());
		self::assertNull($userManager->getUser('badUser'));
	}
	
	public function testCustomHash()
	{
		$appMode = self::$app->getConfigurationType();
		self::$app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
			
		$config = self::$configPhp;
		$password = $config['users'][0]['password'];
		$config['users'][0]['password'] = strrev($password);
		
		$userManager = new TUserManager();
		$userManager->attachBehavior(null, new CustomTestHashBehavior());
		$userManager->init($config);
		$userManager->setPasswordMode('reverse');
		
		self::assertTrue($userManager->validateUser('Mary', $password));
		self::assertTrue($userManager->validateUser('mary', $password));
		self::assertFalse($userManager->validateUser('Mary', $password . '-'));
		self::assertFalse($userManager->validateUser('Amy', $password));
	}
}
