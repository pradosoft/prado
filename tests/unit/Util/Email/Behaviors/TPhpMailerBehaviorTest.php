<?php

namespace Prado\Test\Unit\Util\Email\Behaviors;

class TPhpMailerBehaviorTest extends \PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$behavior = new \Prado\Util\Email\Behaviors\TPhpMailerBehavior();
		$this->assertInstanceOf(\Prado\Util\TBehavior::class, $behavior);
	}
	
	public function testGetPhpMailer()
	{
		$behavior = new \Prado\Util\Email\Behaviors\TPhpMailerBehavior();
		$this->assertNull($behavior->getPhpMailer());
	}
	
	public function testSetPhpMailer()
	{
		$mailer = $this->createMock(\PHPMailer\PHPMailer\PHPMailer::class);
		$behavior = new \Prado\Util\Email\Behaviors\TPhpMailerBehavior();
		$behavior->setPhpMailer($mailer);
		$this->assertSame($mailer, $behavior->getPhpMailer());
	}
}