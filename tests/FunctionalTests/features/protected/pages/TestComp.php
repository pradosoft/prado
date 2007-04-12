<?php

Prado::using('Application.pages.MyJavascriptLib');

class TestComp extends TControl
{
	private $_class;
	public function setClass($value)
	{
		$this->_class=$value;
	}

	public function onPreRender($param)
	{
		parent::onPreRender($param);
		MyJavascriptLib::registerPackage($this,$this->_class);
	}
}

?>