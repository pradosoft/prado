<?php

namespace Prado\Test\Unit\Util\Email\Behaviors;

class TNativeMailBehaviorTest extends \PHPUnit\Framework\TestCase
{
	public function testAttach()
	{
		$behavior = new \Prado\Util\Email\Behaviors\TNativeMailBehavior();
		// Just make sure it doesn't throw an error - functionality is part of parent implementation
		$this->assertInstanceOf(\Prado\Util\TBehavior::class, $behavior);
	}
	
	public function testGetPriority()
	{
		$behavior = new \Prado\Util\Email\Behaviors\TNativeMailBehavior();
		// This is a default priority value based on behavior type
		$this->assertGreaterThanOrEqual(0, $behavior->getPriority());
	}
}