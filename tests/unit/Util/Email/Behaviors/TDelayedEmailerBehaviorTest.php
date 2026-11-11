<?php

namespace Prado\Test\Unit\Util\Email\Behaviors;

use \Prado\Util\Email\Behaviors\TDelayedEmailerBehavior;
use \Prado\Util\TBehavior;

class TDelayedEmailerBehaviorTest extends \PHPUnit\Framework\TestCase
{
	public function testConstruct()
	{
		$behavior = new TDelayedEmailerBehavior();
		$this->assertInstanceOf(TBehavior::class, $behavior);
	}
}