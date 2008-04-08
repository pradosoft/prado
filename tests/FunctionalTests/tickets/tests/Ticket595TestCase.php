<?php

class Ticket595TestCase extends SeleniumTestCase
{
	function test()
	{
		$base="ctl0_Content_";
		$this->open('tickets/index.php?page=Ticket595');
		$this->assertTitle("Verifying Ticket 595");
		
		$this->click($base.'ctl2');
        $this->assertAttribute($base.'A@class','errorclassA');
		
		$this->type($base.'A', 'Prado');
		$this->click($base.'ctl2');
        $this->assertAttribute($base.'A@class','errorclassA');
		
		$this->type($base.'A', 'test@pradosoft.com');
		$this->click($base.'ctl2');
		$this->pause(800);
        $this->assertAttribute($base.'A@class','');


		$this->click($base.'ctl5');
		$this->pause(800);
        $this->assertAttribute($base.'B@class','errorclassB');
		
		$this->type($base.'B', 'Prado');
		$this->click($base.'ctl5');
		$this->pause(800);
        $this->assertAttribute($base.'B@class','errorclassB');
		
		$this->type($base.'B', 'test@pradosoft.com');
		$this->click($base.'ctl5');
		$this->pause(800);
        $this->assertAttribute($base.'B@class','');
	}
}
?>
