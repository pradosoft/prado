<?php

class testInitialValueRequired extends TPage
{

}

class InitialValueRequiredTestCase extends SeleniumTestCase
{
	function test()
	{
		$page = Prado::getApplication()->getTestPage(__FILE__);
		$this->open($page);
		$this->assertTitle("InitialValue Validation Test");
		$this->assertNotVisible("ctl0_Content_validator1");
		$this->type("ctl0_Content_text1", "hello");
		$this->clickAndWait("ctl0_Content_submit");
		$this->assertNotVisible("ctl0_Content_validator1");
		$this->type("ctl0_Content_text1", "test");
		$this->click("ctl0_Content_submit");
		$this->assertVisible("ctl0_Content_validator1");
	}
}

?>