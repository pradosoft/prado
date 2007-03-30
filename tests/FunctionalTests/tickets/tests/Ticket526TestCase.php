<?php

class Ticket526TestCase extends SeleniumTestCase
{
	function test()
	{
		$base="ctl0_Content_";
		$this->open('tickets/index.php?page=Ticket526');
		$this->verifyTitle("Verifying Ticket 526", "");

		$this->assertElementNotPresent("{$base}dpbutton");

		$this->click("{$base}btn");
		$this->pause(800);
		$this->assertElementPresent("{$base}dpbutton");
	}
}
?>