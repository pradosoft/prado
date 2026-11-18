<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\Web\UI\TBroadcastEventParameter;

class TBroadcastEventParameterTest extends \PHPUnit\Framework\TestCase
{
	public function testDefaults(): void
	{
		$param = new TBroadcastEventParameter();
		$this->assertSame('', $param->getName());
		$this->assertNull($param->getParameter());
	}

	public function testNameAndParameter(): void
	{
		$param = new TBroadcastEventParameter('OnPing', 42);
		$this->assertSame('OnPing', $param->getName());
		$this->assertSame('OnPing', $param->getEventName());
		$this->assertSame(42, $param->getParameter());
	}

	public function testSetEventNameSetsOnlyAnEmptyName(): void
	{
		$param = new TBroadcastEventParameter();
		$param->setEventName('OnFirst');
		$this->assertSame('OnFirst', $param->getName());
		$param->setEventName('OnSecond');
		$this->assertSame('OnFirst', $param->getName());
	}

	public function testSetNameReplacesTheName(): void
	{
		$param = new TBroadcastEventParameter('OnFirst');
		$param->setName('OnSecond');
		$this->assertSame('OnSecond', $param->getName());
	}
}
