<?php

class Ticket_27 extends TPage
{


}

class Ticket_27_TestCase extends SeleniumTestCase
{

	function test()
	{
		$this->open($this->getPage($this));
		$this->verifyTitle("Verifying Ticket 27", "");
		$this->click("//input[@value='Agree']", "");
		$this->assertVisible("ctl0_Content_validator1", "");
		$this->type("ctl0_Content_TextBox", "122");
		$this->assertNotVisible("ctl0_Content_validator1", "");
		$this->clickAndWait("//input[@value='Disagree']", "");
		$this->assertNotVisible("ctl0_Content_validator1", "");
	}
}

?>