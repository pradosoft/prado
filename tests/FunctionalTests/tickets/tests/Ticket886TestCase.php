<?php

class Ticket886TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket886');
		$this->assertTitle("Verifying Ticket 886");
		$base = 'ctl0_Content_';
		$this->clickAndWait($base.'SendButton');
		$this->assertTextPresent('2008-01-01');
	}
}

?>
