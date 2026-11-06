<?php

namespace Prado\Test\Unit\Data\ActiveRecord\Scaffold;

use Prado\Web\UI\TControl;

/**
 * Container control that records the command events bubbled to it and stops them.
 */
class BubbleRecorder extends TControl
{
	/** @var array list of [sender, command name] */
	public array $events = [];

	public function bubbleEvent($sender, $param)
	{
		$this->events[] = [$sender, $param->getCommandName()];
		return true;
	}
}
