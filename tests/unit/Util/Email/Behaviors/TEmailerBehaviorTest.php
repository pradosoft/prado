<?php

namespace Prado\Test\Unit\Util\Email\Behaviors;

class TEmailerBehaviorTest extends \PHPUnit\Framework\TestCase
{
	public function testGetEmailerModule()
	{
		$behavior = new \Prado\Util\Email\Behaviors\TEmailerBehavior();
		$this->assertNull($behavior->getEmailerModule());
	}

	public function testSetEmailerModule()
	{
		$module = $this->createMock(\Prado\Util\Email\TEmailModule::class);
		$behavior = new \Prado\Util\Email\Behaviors\TEmailerBehavior();
		$behavior->setEmailerModule($module);
		$this->assertSame($module, $behavior->getEmailerModule());
	}

	public function testCreateEmail()
	{
		$module = $this->createMock(\Prado\Util\Email\TEmailModule::class);
		$module->expects($this->once())
			->method('createEmail')
			->with('test.email', 'test@example.com')
			->willReturn('test returned email');
		
		$behavior = new \Prado\Util\Email\Behaviors\TEmailerBehavior();
		$behavior->setEmailerModule($module);
		
		$result = $behavior->createEmail('test.email', 'test@example.com');
		$this->assertEquals('test returned email', $result);
	}

	public function testSendEmail()
	{
		$module = $this->createMock(\Prado\Util\Email\TEmailModule::class);
		$module->expects($this->once())
			->method('sendEmail')
			->with('test email', ['test' => 'data'], true)
			->willReturn(true);
		
		$behavior = new \Prado\Util\Email\Behaviors\TEmailerBehavior();
		$behavior->setEmailerModule($module);
		
		$result = $behavior->sendEmail('test email', ['test' => 'data'], true);
		$this->assertTrue($result);
	}

	public function testSendMail()
	{
		$module = $this->createMock(\Prado\Util\Email\TEmailModule::class);
		$module->expects($this->once())
			->method('sendMail')
			->with('test@example.com', 'Subject', 'Message', [], [], true)
			->willReturn(true);
		
		$behavior = new \Prado\Util\Email\Behaviors\TEmailerBehavior();
		$behavior->setEmailerModule($module);
		
		$result = $behavior->sendMail('test@example.com', 'Subject', 'Message', [], [], true);
		$this->assertTrue($result);
	}
}