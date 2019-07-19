<?php

class Ticket653TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		// Open with 'Friendly URL'
		$this->url('tickets/index.php/ticket653');
		$this->assertEquals("Verifying Ticket 653", $this->title());

		$this->assertText('textspan', 'This is the page for Ticket653');
	}
}
