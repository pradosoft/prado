<?php

class Ticket463TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket463');
		$this->verifyTitle("Verifying Ticket 463", "");
		$this->assertTextPresent('May 1, 2005 12:00:00 AM');
	}
}

?>