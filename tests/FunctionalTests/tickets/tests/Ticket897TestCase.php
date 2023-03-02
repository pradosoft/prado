<?php

class Ticket897TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket897');
		$this->assertEquals($this->title(), "Verifying Ticket 897");
		$base = 'ctl0_Content_';

		$this->select($base . 'Date_month', 10);
		$this->select($base . 'Date_day', 22);

		$this->byId($base . 'SendButton')->click();
		$this->assertSourceContains(date('Y') . '-10-22');
	}
}
