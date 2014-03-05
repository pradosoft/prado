<?php
class Ticket660TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket660');
		$this->assertEquals($this->title(), "Verifying Ticket 660");

		$this->byId($base.'PB')->click();
		$this->pause(800);
		$this->assertText($base.'A','ÄÖÜ äöü');

		$this->type($base.'T', 'äää');
		$this->byId($base.'PB')->click();
		$this->pause(800);
		$this->assertText($base.'A','äääÄÖÜ äöü');
/*
		// CALLBACK CURRENTLY CAN'T WORK ON NON-UTF8 strings
		$this->type($base.'T', 'ööö');
		$this->byId($base.'CB')->click();
		$this->pause(800);
		$this->assertText($base.'A','öööÄÖÜ äöü');
*/
	}

}
