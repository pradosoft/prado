<?php

class Ticket227TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket227');
		$this->assertTitle('Verifying Ticket 227');
	}
}

?>