<?php

class Ticket163TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket163');
		$this->assertContains('kr 100,00', $this->source());
		$this->assertContains('kr 0,00', $this->source());
		$this->assertContains('-kr 100,00', $this->source());
	}
}
