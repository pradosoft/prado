<?php

class BasePage extends TPage
{
	private $_param1='default 1';
	private $_param2='default 2';
	private $_param3='default 3';
	private $_param4='default 4';
	private $_param5='default 5';

	public function onInit($param)
	{
		parent::onInit($param);
		$this->Title=$this->PagePath;
	}

	public function getParam1()
	{
		return $this->_param1;
	}

	public function setParam1($value)
	{
		$this->_param1=$value;
	}

	public function getParam2()
	{
		return $this->_param2;
	}

	public function setParam2($value)
	{
		$this->_param2=$value;
	}

	public function getParam3()
	{
		return $this->_param3;
	}

	public function setParam3($value)
	{
		$this->_param3=$value;
	}

	public function getParam4()
	{
		return $this->_param4;
	}

	public function setParam4($value)
	{
		$this->_param4=$value;
	}

	public function getParam5()
	{
		return $this->_param5;
	}

	public function setParam5($value)
	{
		$this->_param5=$value;
	}
}

?>