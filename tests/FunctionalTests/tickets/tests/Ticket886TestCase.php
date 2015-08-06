<?php

class Ticket886TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket886');
		$this->assertEquals($this->title(), "Verifying Ticket 886");
		$base = 'ctl0_Content_';
		$this->byId($base.'SendButton')->click();
		$this->assertSourceContains(date('Y').'-01-01');
	}
}

