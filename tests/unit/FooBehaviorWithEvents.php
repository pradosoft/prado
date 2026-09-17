<?php

namespace Prado\Test\Unit;


class FooBehaviorWithEvents extends FooBehavior
{
	public function events()
	{
		return ['onMyEvent' => ['fooEventHandler', function ($sender, $param) { return time(); }]];
	}
	public function fooEventHandler($sender, $param)
	{
		return true;
	}
}
