<?php

namespace Prado\Test\Unit\Util\Cron;

use Prado\Util\Cron\TCronTask;

class TTestCronModuleTask extends TCronTask
{
	public $testunit;
	
	private $_propertyA;
	
	public function getPropertyA(){return $this->_propertyA;}
	public function setPropertyA($v){$this->_propertyA = $v;}
	
	public function execute($cron)
	{
		if($this->testunit)
			$this->testunit->subTaskTest();
	}
	
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		$exprops[] = "testunit";
	}
}
