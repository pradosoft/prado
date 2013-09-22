<?php

class Ticket285TestCase extends PradoGenericSeleniumTest
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket285');
		$this->assertTextPresent('350.00');
		$this->assertTextPresent('349.99');
	}
}