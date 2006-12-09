<?php

class Ticket439TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket439');
		$this->assertTitle("Verifying Ticket 439");
		$this->click("{$base}button1");
		$this->waitForPageToLoad(3000);
		$this->pause(800);
		$this->assertTitle("Verifying Ticket Home");
	}
}
?>