<?php

class Ticket246TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket246');
		$this->assertTitle('Verifying Ticket 246');
	}
}

?>