<?php

class Ticket27TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket27');
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