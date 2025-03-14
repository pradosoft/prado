<?php

class Ticket246TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket246');
		$this->assertTitle('Verifying Ticket 246');
	}
}
