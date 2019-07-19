<?php

class Ticket285TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket285');
		$this->assertSourceContains('350.00');
		$this->assertSourceContains('349.99');
	}
}
