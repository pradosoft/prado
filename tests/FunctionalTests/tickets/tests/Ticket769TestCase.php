<?php

class Ticket769TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base="ctl0_Content_";
		$this->url('tickets/index.php?page=Ticket769');
		$this->assertEquals($this->title(), "Verifying Ticket 769");
		
		$this->click($base.'ctl0');
		$this->assertVisible($base.'ctl1');
		
		$this->type($base.'T1', 'Prado');
		$this->click($base.'ctl0');
		$this->pause(800);
		$this->assertNotVisible($base.'ctl1');
		$this->assertValue($base.'ctl0', 'T1 clicked' );
		
		$this->click($base.'ctl2');
		$this->pause(800);
		$this->assertText($base.'B', 'This is B');
		$this->click($base.'ctl3');
		$this->pause(800);
		
		$this->type($base.'T1', '');
		$this->click($base.'ctl0');
		$this->assertVisible($base.'ctl1');
		$this->type($base.'T1', 'Prado');
		$this->click($base.'ctl0');
		$this->pause(800);
		$this->assertNotVisible($base.'ctl1');
		$this->assertValue($base.'ctl0', 'T1 clicked clicked' );
	}
}