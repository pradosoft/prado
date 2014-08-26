<?php

class Ticket54TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket54');
		$this->assertContains("|A|a|B|b|C|", $this->source());
	}
}
