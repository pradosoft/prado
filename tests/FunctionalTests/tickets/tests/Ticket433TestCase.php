<?php

class Ticket433TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket433');
		$this->assertTitle("Verifying Ticket 433");
		$this->assertText("{$base}VoteClick", "BEFORE click");

		$this->click("{$base}VoteClick");
		$this->pause(800);
		$this->assertText("{$base}VoteClick", "AFTER click CALLBACK DONE");
	}
}

?>