<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\TApplicationComponent;
use Prado\Util\Cron\TCronTaskInfo;
use Prado\Util\IDynamicMethods;

class TTestCronFXComponent extends TApplicationComponent implements IDynamicMethods
{
	public $dyMethod;
	public $args;
	
	public function __dycall($method, $args)
	{
		$this->dyMethod = $method;
		$this->args = $args;
		if ($method == 'fxgetcrontaskinfos')
			return new TCronTaskInfo('taskName', 'taskDefinition', 'module1', 'text title', 'text description');
	}
}
