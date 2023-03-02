<?php

class Ticket284TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket284');
		$this->assertSourceContains('Verifying Ticket 284');
		$this->byId('ctl0_Content_ctl1')->click();
	}
}
