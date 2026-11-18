<?php

namespace Prado\Test\Unit\Email;

use PHPUnit\Framework\TestCase;
use Prado\Email\TEmail;
use Prado\Email\TEmailEventParameter;
use Prado\IEventStoppableParameter;
use Prado\TComponent;

class TEmailEventParameterTest extends TestCase
{
	public function testParameter()
	{
		$email = new TEmail();
		$param = new TEmailEventParameter($email);
		$this->assertInstanceOf(IEventStoppableParameter::class, $param);
		$this->assertSame($email, $param->getEmail());
		$this->assertNull($param->getException());
		$this->assertFalse($param->getIsCancelled());
		$this->assertFalse($param->getStopped());
		$failure = new \RuntimeException('boom');
		$this->assertSame($failure, (new TEmailEventParameter($email, $failure))->getException());
	}

	public function testCancelStopsTheEvent()
	{
		$param = new TEmailEventParameter(new TEmail());
		$param->cancel();
		$this->assertTrue($param->getIsCancelled());
		$this->assertTrue($param->getStopped());
		$other = new TEmailEventParameter(new TEmail());
		$other->stopImmediatePropagation();
		$this->assertTrue($other->getIsCancelled());
	}

	public function testCancelSkipsLaterHandlers()
	{
		$component = new class () extends TComponent {
			public function onSendEmail($param)
			{
				$this->raiseEvent('onSendEmail', $this, $param);
			}
		};
		$calls = [];
		$component->attachEventHandler('onSendEmail', function ($sender, $param) use (&$calls) {
			$calls[] = 'first';
			$param->cancel();
		});
		$component->attachEventHandler('onSendEmail', function ($sender, $param) use (&$calls) {
			$calls[] = 'second';
		});
		$component->onSendEmail(new TEmailEventParameter(new TEmail()));
		$this->assertSame(['first'], $calls);
	}
}
