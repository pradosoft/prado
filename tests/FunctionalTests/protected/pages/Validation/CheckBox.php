<?php

class CheckBox extends TPage
{
	protected function onLoad($param)
	{
		if(!$this->IsPostBack)
			$this->dataBind();
	}
}

class CheckBoxTestCase extends SeleniumTestCase
{
	function setup()
	{
	    $this->open(Prado::getApplication()->getTestPage(__FILE__));
	}

	function testValidator()
	{
		$this->verifyTitle("An AutoPostBack CheckBox");

		//test checkbox 2 should fire the validator
		$this->assertNotVisible("validator1");
		$this->click("checkbox2");
		$this->pasue(100);
		$this->assertVisible("validator1");
	}
}

?>