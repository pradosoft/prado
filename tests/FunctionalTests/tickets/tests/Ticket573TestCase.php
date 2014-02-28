<?php

class Ticket573TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket573');
		$this->verifyTitle("Verifying Ticket 573", "");

		$this->assertText('test1', '10.00');
	}
}
