<?php

class Ticket27TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket27');
		$this->assertTitle("Verifying Ticket 27");
		$this->byXPath("//input[@value='Agree']")->click();
		$this->assertVisible("ctl0_Content_validator1");
		$this->type("ctl0_Content_TextBox", "122");
		$this->assertNotVisible("ctl0_Content_validator1");
		$this->byXPath("//input[@value='Disagree']")->click();
		$this->assertNotVisible("ctl0_Content_validator1");
	}
}
