<?php

class Ticket769TestCase extends SeleniumTestCase
{
	function test()
	{
		$base="ctl0_Content_";
		$this->open('tickets/index.php?page=Ticket769');
		$this->assertTitle("Verifying Ticket 769");
		
		$this->click($base.'ctl0');
		$this->assertVisible($base.'ctl1');
		
		$this->type($base.'T1', 'Prado');
		$this->click($base.'ctl0');
		$this->pause(800);
		$this->assertNotVisible($base.'ctl1');
		$this->verifyTextPresent($base.'ctl0', 'T1 clicked' );
		
		$this->click($base.'ctl2');
		$this->pause(800);
		$this->verifyTextPresent($base.'B', 'This is B');
		$this->click($base.'ctl3');
		$this->pause(800);
		
		$this->type($base.'T1', '');
		$this->click($base.'ctl0');
		$this->assertVisible($base.'ctl1');
		$this->type($base.'T1', 'Prado');
		$this->click($base.'ctl0');
		$this->pause(800);
		$this->assertNotVisible($base.'ctl1');
		$this->verifyTextPresent($base.'ctl0', 'T1 clicked clicked' );
		
	}
}
?>