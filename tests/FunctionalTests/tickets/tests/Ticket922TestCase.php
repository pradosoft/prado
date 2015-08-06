<?php

class Ticket922TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url('tickets/index.php?page=Ticket922');
		$this->assertEquals($this->title(), "Verifying Ticket 922");
		$base = 'ctl0_Content_';

		$this->type($base.'Text', 'two words');
		$this->byName('ctl0$Content$ctl0')->click();
		$this->pause(50);
		$this->assertText($base.'Result','two words');

	}
}

