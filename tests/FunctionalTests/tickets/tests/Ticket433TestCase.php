<?php

class Ticket433TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket433');
		$this->assertEquals($this->title(), "Verifying Ticket 433");
		$this->assertText("{$base}VoteClick", "BEFORE click");

		$this->byId("{$base}VoteClick")->click();
		$this->pauseFairAmount();
		$this->assertText("{$base}VoteClick", "AFTER click CALLBACK DONE");
	}
}
