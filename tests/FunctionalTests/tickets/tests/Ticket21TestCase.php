<?php

class Ticket21TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket21');
		$this->assertTitle("Verifying Ticket 21");
		$this->byId("ctl0_Content_button1")->click();
		$this->assertSourceContains("Radio button clicks: 1");
		$this->byId("ctl0_Content_button1")->click();
		$this->assertSourceContains("Radio button clicks: 1");
	}
}
