<?php

class Ticket21TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket21');
		$this->assertEquals($this->title(), "Verifying Ticket 21");
		$this->clickAndWait("ctl0_Content_button1");
		$this->assertTextPresent("Radio button clicks: 1", "");
		$this->click("ctl0_Content_button1");
		$this->assertTextPresent("Radio button clicks: 1", "");
	}
}
