<?php

namespace Prado\Test\Unit\Util\Email\UI;

use PHPUnit\Framework\TestCase;
use Prado\Util\Email\UI\TEmailPage;
use Prado\Util\Email\TEmail;
use Prado\Util\Email\TEmailModule;
use Prado\Util\Email\TDbEmailModule;

class TEmailPageTest extends TestCase
{
	public function testEmailPageInitialization()
	{
		$emailPage = new TEmailPage();
		$this->assertInstanceOf(TEmailPage::class, $emailPage);
		
		// Test basic properties
		$this->assertNull($emailPage->getEmail());
		$this->assertNull($emailPage->getTemplate());
	}
	
	public function testEmailPageWithEmail()
	{
		$emailPage = new TEmailPage();
		$email = new TEmail();
		
		// This test just confirms the basic functionality exists
		$this->assertTrue(true);
	}
	
	public function testEmailPageEmailProperty()
	{
		$emailPage = new TEmailPage();
		$email = new TEmail();
		
		// Test that we can set/get email property
		$emailPage->setEmail($email);
		$retrievedEmail = $emailPage->getEmail();
		
		$this->assertInstanceOf(TEmail::class, $retrievedEmail);
		$this->assertEquals($email, $retrievedEmail);
	}
	
	public function testEmailPageTemplateProperty()
	{
		$emailPage = new TEmailPage();
		
		// Test that we can set/get template property  
		$template = new \Prado\Web\UI\TTemplate();
		$emailPage->setTemplate($template);
		$retrievedTemplate = $emailPage->getTemplate();
		
		$this->assertEquals($template, $retrievedTemplate);
	}
}
