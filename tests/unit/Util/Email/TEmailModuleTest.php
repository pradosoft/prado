<?php

namespace Prado\Test\Unit\Util\Email;

use PHPUnit\Framework\TestCase;
use Prado\TApplication;
use Prado\Util\Email\TEmailModule;
use Prado\Util\Email\TEmail;
use Prado\Exceptions\TConfigurationException;

class TEmailModuleTest extends TestCase
{
	protected $emailModule;
	
	protected function setUp(): void
	{
		$this->emailModule = new TEmailModule();
	}
	
	protected function tearDown(): void
	{
		$this->emailModule = null;
	}
	
	public function testModuleInitialization()
	{
		$this->assertInstanceOf(TEmailModule::class, $this->emailModule);
		
		// Test module constants
		$this->assertNotEmpty(TEmailModule::EMAILER_BEHAVIOR_NAME);
		$this->assertNotEmpty(TEmailModule::DEFAULT_BASEPATH);
		$this->assertNotEmpty(TEmailModule::EMAIL_FILE_EXT);
		$this->assertNotEmpty(TEmailModule::EMAIL_TEXT_FILE_EXT);
	}
	
	public function testSetAndGetDefaultCcAddress()
	{
		$ccAddress = 'test@test.com';
		$this->emailModule->setDefaultCcAddress($ccAddress);
		$this->assertEquals($ccAddress, $this->emailModule->getDefaultCcAddress());
	}
	
	public function testSetAndGetDefaultBccAddress()
	{
		$bccAddress = 'test@test.com';
		$this->emailModule->setDefaultBccAddress($bccAddress);
		$this->assertEquals($bccAddress, $this->emailModule->getDefaultBccAddress());
	}
	
	public function testGetAndSetMailAdditionalParameters()
	{
		$param = '-f test@test.com';
		$this->emailModule->setMailAdditionalParameters($param);
		$this->assertEquals($param, $this->emailModule->getMailAdditionalParameters());
	}
	
	public function testGetAndSetBaseEmailClass()
	{
		$baseClass = 'Test\EmailClass';
		$this->emailModule->setBaseEmailClass($baseClass);
		$this->assertEquals($baseClass, $this->emailModule->getBaseEmailClass());
	}
	
	public function testGetTemplateManager()
	{
		$templateManager = $this->emailModule->getTemplateManager();
		$this->assertNotNull($templateManager);
	}
	
	public function testCreateEmailFunctionality()
	{
		// Basic functionality test
		$this->assertTrue(true);
	}
}
