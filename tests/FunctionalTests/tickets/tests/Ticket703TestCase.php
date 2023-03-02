<?php

class Ticket703TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket703.Ticket703');
		$this->assertEquals($this->title(), "Verifying Ticket703.Ticket703 703.703");
		// Start with an empty log
		$this->byId($base . 'ctl2')->click();
		// Wait for callback to be lanched
		$this->pause(1000);
		$this->assertText($base . 'logBox', "");

		$this->type($base . 'logMessage', "Test of prado logging system");
		$this->byId($base . 'ctl0')->click();
		$this->pauseFairAmount();
		$this->byId($base . 'ctl1')->click();
		$this->pause(1000);
		$this->assertStringContainsString("Test of prado logging system", $this->byId($base . 'logBox')->value());

		// Clean log for next run
		$this->byId($base . 'ctl2')->click();
		// Wait for callback to be lanched
		$this->pause(1000);
		$this->assertText($base . 'logBox', "");
	}
}
