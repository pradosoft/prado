<?php

class Ticket54TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket54');
		$this->assertSourceContains("|A|a|B|b|C|");
	}
}
