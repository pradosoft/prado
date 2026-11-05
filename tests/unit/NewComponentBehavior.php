<?php

namespace Prado\Test\Unit;

use Prado\Util\TBehavior;

class NewComponentBehavior extends TBehavior
{
	public $data = 0;
	public function events()
	{
		return ['onMyEvent' => 'ncBehaviorHandler'];
	}

	public function ncBehaviorHandler($sender, $param)
	{
	}
}
