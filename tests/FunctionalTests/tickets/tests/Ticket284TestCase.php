<?php

class Ticket284TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket284');
		$this->assertTextPresent('Verifying Ticket 284');
		$this->click('ctl0_Content_ctl1');

	}
}
