<?php
class Ticket660TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket660');
		$this->assertTitle("Verifying Ticket 660");
		
		$this->click($base.'PB');
		$this->pause(800);
		$this->assertText($base.'A','ÄÖÜ äöü');

		$this->type($base.'T', 'äää');
		$this->click($base.'PB');
		$this->pause(800);
		$this->assertText($base.'A','äääÄÖÜ äöü');

		$this->type($base.'T', 'ööö');
		$this->click($base.'CB');
		$this->pause(800);
		$this->assertText($base.'A','öööÄÖÜ äöü');
	}

}
?>
