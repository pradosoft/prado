<?php

class Ticket922TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket922');
		$this->assertTitle("Verifying Ticket 922");
		$base = 'ctl0_Content_';

		$this->type($base . 'Text', 'two words');
		$this->byId($base . 'Button')->click();
		$this->assertText($base . 'Result', 'two words');
	}
}
