<?php

class Ticket653TestCase extends PradoGenericSeleniumTest
{
	function test()
	{
		// Open with 'Friendly URL'
		$this->open('tickets/index.php/ticket653');
		$this->verifyTitle("Verifying Ticket 653", "");

		$this->assertText('textspan', 'This is the page for Ticket653');
	}
}
