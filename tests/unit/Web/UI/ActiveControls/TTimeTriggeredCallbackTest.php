<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TTimeTriggeredCallback;

/**
 * The PauseWhenHidden property of TTimeTriggeredCallback and the client options it sends.
 */
class TTimeTriggeredCallbackTest extends TestCase
{
	public function testPauseWhenHiddenDefaultsToTrueAndIsNotSentToTheClient()
	{
		$timer = new TTimeTriggeredCallback();
		$timer->setID('timer');
		$this->assertTrue($timer->getPauseWhenHidden());
		$options = PradoUnit::invoke($timer, 'getTriggerOptions');
		$this->assertArrayNotHasKey('PauseWhenHidden', $options);
		$this->assertSame(1, $options['Interval']);
	}

	public function testPauseWhenHiddenFalseIsSentToTheClient()
	{
		$timer = new TTimeTriggeredCallback();
		$timer->setID('timer');
		$timer->setPauseWhenHidden('false');
		$this->assertFalse($timer->getPauseWhenHidden());
		$options = PradoUnit::invoke($timer, 'getTriggerOptions');
		$this->assertFalse($options['PauseWhenHidden']);
		$timer->setPauseWhenHidden(1);
		$this->assertTrue($timer->getPauseWhenHidden());
	}
}
