<?php

class Ticket463TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket463');
		$this->assertEquals("Verifying Ticket 463", $this->title());
		$this->assertStringContainsString('May 1, 2005 at 12:00:00 AM', $this->source());
	}
}
