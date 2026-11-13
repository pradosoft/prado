<?php

namespace Prado\Test\Unit\Security;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TApplication;
use Prado\Security\TUserManager;
use Prado\Xml\TXmlDocument;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Security\TUserManagerPasswordMode;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\THttpCookie;

class TUserManagerTest extends \PHPUnit\Framework\TestCase
{
	protected ?TTestApplication $app = null;
	public static $config = null;


	protected function setUp(): void
	{
		$this->app = new TTestApplication(__DIR__ . '/app');
		Prado::setPathOfAlias('App', __DIR__);

		if (self::$config === null) {
			// Simulate a config file
			self::$config = new TXmlDocument('1.0', 'utf8');
			self::$config->loadFromString('<users><user name="Joe" password="demo"/><user name="John" password="demo" /><user name="test" password="test" roles="Reader, User"/><role name="Administrator" users="John" /><role name="Writer" users="Joe, John" /></users>');
		}
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
		$userManager = new TUserManager();
		$userManager->init(self::$config);
		self::assertEquals(['joe' => 'demo', 'john' => 'demo', 'test' => 'test'], $userManager->getUsers());
		$userManager = null;
		// Test with a file
		if (is_writable(__DIR__)) {
			self::$config->saveToFile(__DIR__ . '/users.xml');
			$userManager = new TUserManager();
			$userManager->setUserFile('App.users');
			$userManager->init(new TXmlDocument()); // Empty config
			self::assertEquals(['joe' => 'demo', 'john' => 'demo', 'test' => 'test'], $userManager->getUsers());
			unlink(__DIR__ . '/users.xml');
		}
	}

	public function testUsers()
	{
		$userManager = new TUserManager();
		$userManager->init(self::$config);
		self::assertEquals(['joe' => 'demo', 'john' => 'demo', 'test' => 'test'], $userManager->getUsers());
	}

	public function testRoles()
	{
		$userManager = new TUserManager();
		$userManager->init(self::$config);
		self::assertEquals(['joe' => ['Writer'], 'john' => ['Administrator', 'Writer'], 'test' => ['Reader', 'User']], $userManager->getRoles());
	}

	public function testUserFile()
	{
		$userManager = new TUserManager();
		try {
			$userManager->setUserFile('invalidFile');
			self::fail('Exception TConfigurationException not thrown');
		} catch (TConfigurationException $e) {
		}
		$userManager = null;
		if (is_writable(__DIR__)) {
			self::$config->saveToFile(__DIR__ . '/users.xml');
			$userManager = new TUserManager();
			$userManager->setUserFile('App.users');
			$userManager->init(new TXmlDocument()); // Empty config
			self::assertEquals(str_replace('\\', '/', __DIR__ . '/users.xml'), str_replace('\\', '/', $userManager->getUserFile()));
			unlink(__DIR__ . '/users.xml');
			$userManager = null;
		}
		$userManager = new TUserManager();
		$userManager->init(self::$config);
		try {
			$userManager->setUserFile('App.users');
			self::fail('Exception TInvalidOperationException not thrown');
		} catch (TInvalidOperationException $e) {
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
		try {
			$userManager->setPasswordMode('Invalid');
			self::fail('Exception TInvalidDataValueException not thrown');
		} catch (TInvalidDataValueException $e) {
		}
	}

	public function testValidateUser()
	{
		$userManager = new TUserManager();
		$userManager->init(self::$config);
		$userManager->setPasswordMode('Clear');
		self::assertTrue($userManager->validateUser('Joe', 'demo'));
		self::assertTrue($userManager->validateUser('JOE', 'demo'));
		self::assertFalse($userManager->validateUser('John', 'bad'));
		self::assertFalse($userManager->validateUser('Joe', 'dem'));
		self::assertFalse($userManager->validateUser('Joe', 'demo '));
		self::assertFalse($userManager->validateUser('Joe', ''));
		self::assertFalse($userManager->validateUser('Nobody', 'demo'));
	}

	public function testValidateUserHashedModes()
	{
		$userManager = new TUserManager();
		$userManager->init(self::$config);
		foreach (['MD5' => md5('demo'), 'SHA1' => sha1('demo')] as $mode => $hash) {
			$userManager->setPasswordMode($mode);
			PradoUnit::setProp($userManager, '_users', ['joe' => $hash]);
			self::assertTrue($userManager->validateUser('Joe', 'demo'), $mode);
			self::assertFalse($userManager->validateUser('Joe', 'Demo'), $mode);
		}
	}

	public function testUser()
	{
		$userManager = new TUserManager();
		$userManager->init(self::$config);
		$guest = $userManager->getUser(null);
		self::assertInstanceOf(\Prado\Security\TUser::class, $guest);
		self::assertTrue($guest->getIsGuest());
		$user = $userManager->getUser('joe');
		self::assertInstanceOf(\Prado\Security\TUser::class, $user);
		self::assertEquals('joe', $user->getName());
		self::assertEquals(['Writer'], $user->getRoles());
		self::assertFalse($user->getIsGuest());
		self::assertNull($userManager->getUser('badUser'));
	}

	public function testUserCookie()
	{
		$userManager = new TUserManager();
		$userManager->init(self::$config);
		$userManager->getApplication()->setUser($userManager->getUser('Joe'));
		$cookie = new THttpCookie('auth', '');
		$userManager->saveUserToCookie($cookie);
		$data = json_decode($cookie->getValue(), true);
		self::assertSame('joe', $data[0]);
		self::assertStringNotContainsString(sha1('joedemo'), $cookie->getValue());
		self::assertEquals('joe', $userManager->getUserFromCookie($cookie)->getName());

		$invalid = [
			'',
			'not json',
			json_encode(['joe']),
			json_encode(['joe', $data[1], 'x']),
			json_encode(['joe', 'wrong-token']),
			json_encode(['joe', ['token']]),
			json_encode(['nobody', $data[1]]),
			json_encode(['joe', sha1('joedemo')]),
			serialize(['joe', $data[1]]),
		];
		foreach ($invalid as $value) {
			self::assertNull($userManager->getUserFromCookie(new THttpCookie('auth', $value)), $value);
		}

		$config = new TXmlDocument('1.0', 'utf8');
		$config->loadFromString('<users><user name="Joe" password="changed"/></users>');
		$changed = new TUserManager();
		$changed->init($config);
		self::assertNull($changed->getUserFromCookie($cookie), 'A password change invalidates the token.');
	}

	public function testInitFromPhpConfiguration()
	{
		$this->app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$userManager = new TUserManager();
		$userManager->init([
			'users' => [
				['name' => ' Joe ', 'password' => 'demo', 'roles' => 'Reader, ,User'],
				['name' => 'Mia', 'password' => 'demo'],
				['name' => 'Ned', 'roles' => ''],
			],
			'roles' => [
				['name' => 'Writer', 'users' => 'Joe, ,Mia'],
				['name' => 'Nobody'],
			],
		]);
		self::assertEquals(['joe' => 'demo', 'mia' => 'demo', 'ned' => ''], $userManager->getUsers());
		self::assertEquals(['joe' => ['Reader', 'User', 'Writer'], 'mia' => ['Writer']], $userManager->getRoles());

		$user = $userManager->getUser('Ned');
		self::assertSame('ned', $user->getName());
		self::assertSame([], $user->getRoles());
	}

	public function testInitFromPhpConfigurationIgnoresMissingAndNonArraySections()
	{
		$this->app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		foreach ([null, [], ['users' => 'Joe', 'roles' => 'Writer']] as $config) {
			$userManager = new TUserManager();
			$userManager->init($config);
			self::assertSame([], $userManager->getUsers());
			self::assertSame([], $userManager->getRoles());
		}
	}

	public function testInitFromPhpUserFile()
	{
		$this->app->setConfigurationType(TApplication::CONFIG_TYPE_PHP);
		$userManager = new TUserManager();
		$userManager->setUserFile('App.fixtures.users');
		$userManager->init(['users' => [['name' => 'Joe', 'password' => 'demo']]]);
		self::assertEquals(['joe' => 'demo', 'ann' => 'filepass'], $userManager->getUsers());
		self::assertEquals(['ann' => ['Editor']], $userManager->getRoles());
	}

	public function testSetUserFileRejectsAMissingFile()
	{
		$userManager = new TUserManager();
		$this->expectException(TConfigurationException::class);
		$userManager->setUserFile('App.fixtures.no_such_users');
	}

	public function testSaveUserToCookieLeavesTheCookieForAnUnknownUser()
	{
		$userManager = new TUserManager();
		$userManager->init(self::$config);
		$userManager->getApplication()->setUser($userManager->getUser(null));
		$cookie = new THttpCookie('auth', 'unchanged');
		$userManager->saveUserToCookie($cookie);
		self::assertSame('unchanged', $cookie->getValue());
	}
}
