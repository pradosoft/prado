<?php

class Ticket521TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open("tickets/index.php?page=Ticket521");
		$this->assertTitle("Verifying Ticket 521");
		$this->assertText("{$base}label1", "Label 1");

		$this->click("{$base}button1");
		$this->pause(1200);

		$this->assertText("{$base}label1", "Button 1 was clicked on callback");
	}

}

?>