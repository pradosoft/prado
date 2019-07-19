<?php

class Ticket227TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket227');
		$this->assertEquals($this->title(), 'Verifying Ticket 227');
	}
}
