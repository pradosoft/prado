<?php

namespace Prado\Test\Unit;


class BarClassBehaviorWithEvents extends BarClassBehavior
{
	public function events()
	{
		return ['onMyEvent' => ['barClassEventHandler', function ($sender, $param) { return time(); }]];
	}
	public function barClassEventHandler($sender, $param)
	{
	}
}
