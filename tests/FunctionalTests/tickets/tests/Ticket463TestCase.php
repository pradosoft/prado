<?php

class Ticket463TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket463');
		$this->verifyTitle("Verifying Ticket 463", "");
		$this->assertTextPresent('May 1, 2005 12:00:00 AM');
	}
}
