<?php

class Ticket595TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base="ctl0_Content_";
		$this->url('tickets/index.php?page=Ticket595');
		$this->assertEquals($this->title(), "Verifying Ticket 595");

		$this->click($base.'ctl2');
        $this->verifyAttribute($base.'A@class','errorclassA');

		$this->type($base.'A', 'Prado');
		$this->click($base.'ctl2');
        $this->verifyAttribute($base.'A@class','errorclassA');

		$this->type($base.'A', 'test@pradosoft.com');
		$this->click($base.'ctl2');
		$this->pause(800);
        $this->verifyAttribute($base.'A@class','');

		$this->click($base.'ctl5');
		$this->pause(800);
        $this->verifyAttribute($base.'B@class',' errorclassB');

		$this->type($base.'B', 'Prado');
		$this->click($base.'ctl5');
		$this->pause(800);
        $this->verifyAttribute($base.'B@class',' errorclassB');

		$this->type($base.'B', 'test@pradosoft.com');
		$this->click($base.'ctl5');
		$this->pause(800);
        $this->verifyAttribute($base.'B@class','');
	}
}
