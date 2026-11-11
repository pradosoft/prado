<?php

use \Prado\Util\Email\Behaviors\TDelayedEmailerBehavior;
use \Prado\Util\TBehavior;

/**
 * @package Prado\Util\Email\Tests
 */
class TDelayedEmailerBehaviorTest extends \PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$behavior = new TDelayedEmailerBehavior();
		$this->assertInstanceOf(TBehavior::class, $behavior);
	}
}