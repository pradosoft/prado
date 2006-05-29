<?php

class Ticket163TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket163');
		$this->assertTextPresent('kr 100,00');
		$this->assertTextPresent('kr 0,00');
		$this->assertTextPresent('-kr 100,00');
	}
}

?>