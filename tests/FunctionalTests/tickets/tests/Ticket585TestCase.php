<?php

class Ticket585TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket585');
		$this->verifyTitle("Verifying Ticket 585", "");

		$this->assertText("error", "");
		$this->assertNotVisible("{$base}validator1");

		$this->click("{$base}button1");
		$this->pause(800);
		$this->assertText("error", "Success");
		$this->assertNotVisible("{$base}validator1");

		$this->type("{$base}test", "15-03-2007");
		$this->click("{$base}button1");
		$this->pause(800);
		$this->assertText("error", "Error");
		$this->assertVisible("{$base}validator1");
	}
}

?>