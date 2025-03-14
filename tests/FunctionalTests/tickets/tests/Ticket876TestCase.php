<?php

class Ticket876TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket876');
		$this->assertTitle("Verifying Ticket 876");
		$base = 'ctl0_Content_';

		$this->assertElementPresent('//link[@rel="stylesheet"]');
		$this->byId($base . 'Button')->click();
		$this->assertElementNotPresent('//link[@rel="stylesheet"]');

		/*$this->select($base.'Date_month', 10);
		$this->select($base.'Date_day', 22);

		$this->byId($base.'SendButton')->click();
		$this->assertSourceContains('2008-10-22');*/
	}
}
