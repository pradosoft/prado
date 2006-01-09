<?php

class RequiredFieldValidator extends TPage
{
	function onLoad($param)
	{
		Prado::log("Hello", TLogger::WARNING);
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