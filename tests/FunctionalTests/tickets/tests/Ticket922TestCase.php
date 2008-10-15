<?php

class Ticket922TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket922');
		$this->assertTitle("Verifying Ticket 922");
		$base = 'ctl0_Content_';
		
		$this->type($base.'Text', 'two words');
		$this->clickAndWait('ctl0$Content$ctl0');
		$this->assertText($base.'Result','two words');

	}
}

?>
