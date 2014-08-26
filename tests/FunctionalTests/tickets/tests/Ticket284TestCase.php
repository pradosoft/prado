<?php

class Ticket284TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket284');
		$this->assertContains('Verifying Ticket 284', $this->source());
		$this->byId('ctl0_Content_ctl1')->click();

	}
}
