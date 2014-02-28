<?php
class Ticket703TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket703.Ticket703');
		$this->assertEquals($this->title(), "Verifying Ticket703.Ticket703 703.703");
		// Start with an empty log
		$this->click($base.'ctl2');
		// Wait for callback to be lanched
		$this->pause(1000);
		$this->assertText($base.'logBox', "");

		$this->type($base.'logMessage', "Test of prado logging system");
		$this->click($base.'ctl0');
		$this->pause(800);
		$this->click($base.'ctl1');
		$this->assertTextPresent($base.'logBox', "Test of prado logging system");

		// Clean log for next run
		$this->click($base.'ctl2');
		// Wait for callback to be lanched
		$this->pause(1000);
		$this->assertText($base.'logBox', "");

	}
}