<?php

class Ticket653TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		// Open with 'Friendly URL'
		$this->url('tickets/index.php/ticket653');
		$this->verifyTitle("Verifying Ticket 653", "");

		$this->assertText('textspan', 'This is the page for Ticket653');
	}
}
