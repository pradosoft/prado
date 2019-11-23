<?php

class Ticket439TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket439');
		$this->assertEquals($this->title(), "Verifying Ticket 439");
		$this->byId("{$base}button1")->click();
		$this->pauseFairAmount();
		$this->assertEquals($this->title(), "Verifying Home");
	}
}
