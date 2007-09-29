<?php
class Ticket703TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket703.Ticket703');
		$this->assertTitle("Verifying Ticket 703.703");
		// Start with an empty log
		$this->click($base.'ctl2');
		// Wait for callback to be lanched
		$this->pause(2000);
		$this->assertText($base.'logBox', "");
		$this->type($base.'logMessage', "Test of prado logging system");
		$this->click($base.'ctl0');
		$this->pause(800);
		$this->click($base.'ctl1');
		$this->assertTextPresent($base.'logBox', "Test of prado logging system");			
	}
}
?>