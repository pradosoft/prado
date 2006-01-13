<?php

class RequiredFieldValidator extends TPage
{
	protected function onLoad($param)
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
		$this->assertNotVisible("validator1");
		$this->click("button1");
		$this->assertVisible("validator1");
		$this->type("text1", "test");
		$this->clickAndWait("button1");
		$this->assertNotVisible("validator1");
	}
}

?>