<?php

class Ticket526TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url('tickets/index.php?page=Ticket526');
		$this->assertTitle("Verifying Ticket 526");

		$this->assertElementNotPresent("{$base}dpbutton");

		$this->byId("{$base}btn")->click();
		$this->assertElementPresent("{$base}dpbutton");
	}
}
