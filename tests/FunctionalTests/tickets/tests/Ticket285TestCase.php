<?php

class Ticket285TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket285');
		$this->assertTextPresent('350.00');
		$this->assertTextPresent('349.99');
	}
}