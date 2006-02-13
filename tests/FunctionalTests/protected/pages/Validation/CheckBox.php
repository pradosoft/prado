<?php

class CheckBox extends TPage
{
	public function onLoad($param)
	{
		if(!$this->IsPostBack)
			$this->dataBind();
	}
}

class CheckBoxTestCase2 extends SeleniumTestCase
{
	function setup()
	{
	    $this->open(Prado::getApplication()->getTestPage(__FILE__));
	}

	function testValidator()
	{
		$this->verifyTitle("An AutoPostBack CheckBox");

		//test checkbox 2 should fire the validator
		$this->assertNotVisible("ctl0_Content_validator1");
		$this->click("ctl0_Content_checkbox2");
//		$this->pause(100);
		$this->assertVisible("ctl0_Content_validator1");

		//write some text, and see what it
		$this->type('ctl0_Content_TextBox', "hello");
//		$this->pause(100);
		$this->assertNotVisible("ctl0_Content_validator1");
		$this->clickAndWait("ctl0_Content_checkbox2"); //submit
		$this->assertNotVisible("ctl0_Content_validator1");

	}
}

?>