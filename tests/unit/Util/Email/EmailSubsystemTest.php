<?php

namespace Prado\Test\Unit\Util\Email;

use PHPUnit\Framework\TestCase;
use Prado\Util\Email\TEmailModule;
use Prado\Util\Email\TDbEmailModule;
use Prado\Util\Email\TEmail;
use Prado\Util\Email\UI\TEmailPage;

class EmailSubsystemTest extends TestCase
{
	public function testTEmailModuleExistsAndIsFunctional()
	{
		$emailModule = new TEmailModule();
		$this->assertInstanceOf(TEmailModule::class, $emailModule);
	}
	
	public function testTDbEmailModuleExistsAndExtendsTEmailModule()
	{
		$dbEmailModule = new TDbEmailModule();
		$this->assertInstanceOf(TDbEmailModule::class, $dbEmailModule);
		$this->assertInstanceOf(TEmailModule::class, $dbEmailModule);
	}
	
	public function testTEmailPageExistsAndIsFunctional()
	{
		$emailPage = new TEmailPage();
		$this->assertInstanceOf(TEmailPage::class, $emailPage);
	}
	
	public function testBasicEmailFunctionality()
	{
		$email = new TEmail();
		$this->assertInstanceOf(TEmail::class, $email);
	}
	
	public function testEmailModuleConstants()
	{
		$this->assertNotEmpty(TEmailModule::EMAILER_BEHAVIOR_NAME);
		$this->assertNotEmpty(TEmailModule::DEFAULT_BASEPATH);
		$this->assertNotEmpty(TEmailModule::EMAIL_FILE_EXT);
	}
	
	public function testDbEmailModuleConstants()
	{
		$this->assertNotEmpty(TDbEmailModule::EMAIL_SERVICE);
		$this->assertNotEmpty(TDbEmailModule::PERM_EMAIL_SHELL);
		$this->assertNotEmpty(TDbEmailModule::PERM_EMAIL_LOG_READ);
		$this->assertNotEmpty(TDbEmailModule::PERM_EMAIL_LOG_DELETE);
	}
}
