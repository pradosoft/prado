<?php

class Ticket21TestCase extends SeleniumTestCase
{
	function test()
	{
		//problem with test runner clicking on radio buttons
		$this->skipBrowsers(self::OPERA);

		$this->open('tickets/index.php?page=Ticket21');
		$this->assertTitle("Verifying Ticket 21");
		$this->clickAndWait("ctl0_Content_button1");
		$this->verifyTextPresent("Radio button clicks: 1", "");
		$this->click("ctl0_Content_button1");
		$this->verifyTextPresent("Radio button clicks: 1", "");
	}
}

?>