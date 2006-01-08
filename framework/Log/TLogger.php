<?php

class TLogger extends TComponent
{
	const DEBUG=0x01;
	const INFO=0x02;
	const NOTICE=0x04;
	const WARNING=0x08;
	const ERROR=0x10;
	const ALERT=0x20;
	const FATAL=0x40;
	private $_logs=array();
	private $_levels;
	private $_categories;

	public function log($message,$level,$category='Uncategorized')
	{
		$this->_logs[]=array($message,$level,$category,time());
	}

	public function getLogs($levels=null,$categories=null)
	{
		$this->_levels=$levels;
		$this->_categories=$categories;
		if(empty($levels) && empty($categories))
			return $this->_logs;
		else if(empty($levels))
			return array_values(array_filter(array_filter($this->_logs,array($this,'filterByCategories'))));
		else if(empty($categories))
			return array_values(array_filter(array_filter($this->_logs,array($this,'filterByLevels'))));
		else
		{
			$ret=array_values(array_filter(array_filter($this->_logs,array($this,'filterByLevels'))));
			return array_values(array_filter(array_filter($ret,array($this,'filterByCategories'))));
		}
	}

	private function filterByCategories($value)
	{
		foreach($this->_categories as $category)
		{
			if(strpos($value[2],$category)===0)
				return $value;
		}
		return false;
	}

	private function filterByLevels($value)
	{
		if($value[1] & $this->_levels)
			return $value;
		else
			return false;
	}
}

?>