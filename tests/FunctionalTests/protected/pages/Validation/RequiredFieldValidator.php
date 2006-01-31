<?php

class RequiredFieldValidator extends TPage
{
	public function onLoad($param)
	{
		if(!$this->IsPostBack)
			$this->dataBind();
	}
}

class RequiredFieldTestCase extends SeleniumTestCase
{
	function setup()
	{
	    $this->open(Prado::getApplication()->getTestPage(__FILE__));
	}

	function testValidator()
	{
		$this->assertTextPresent("Basic TRequiredFieldValidator Test1");
		$this->assertNotVisible("ctl0_Content_validator1");
		$this->click("ctl0_Content_button1");
		$this->assertVisible("ctl0_Content_validator1");
		$this->type("ctl0_Content_text1", "test");
		$this->clickAndWait("ctl0_Content_button1");
		$this->assertNotVisible("ctl0_Content_validator1");
	}
}

?>