<?php

class Ticket285TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket285');
		$this->assertContains('350.00', $this->source());
		$this->assertContains('349.99', $this->source());
	}
}