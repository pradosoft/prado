<?php

class Ticket573TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket573');
		$this->verifyTitle("Verifying Ticket 573", "");

		$this->assertText('test1', '10.00');
	}
}

?>