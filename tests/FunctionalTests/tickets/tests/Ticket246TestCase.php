<?php

class Ticket246TestCase extends PradoGenericSeleniumTest
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket246');
		$this->assertTitle('Verifying Ticket 246');
	}
}
