<?php

class Ticket886TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket886');
		$this->assertTitle("Verifying Ticket 886");
		$base = 'ctl0_Content_';
		$this->byId($base . 'SendButton')->click();
		$this->assertSourceContains(date('Y-m-d'));
	}
}
