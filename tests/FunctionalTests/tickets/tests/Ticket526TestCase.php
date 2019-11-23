<?php

class Ticket526TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url('tickets/index.php?page=Ticket526');
		$this->assertEquals("Verifying Ticket 526", $this->title());

		$this->assertElementNotPresent("{$base}dpbutton");

		$this->byId("{$base}btn")->click();
		$this->pauseFairAmount();
		$this->assertElementPresent("{$base}dpbutton");
	}
}
