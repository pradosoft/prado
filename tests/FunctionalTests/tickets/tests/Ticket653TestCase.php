<?php

class Ticket653TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		// Open with 'Friendly URL'
		$this->url('tickets/index.php/ticket653');
		$this->assertTitle("Verifying Ticket 653");

		$this->assertText('textspan', 'This is the page for Ticket653');
	}
}
