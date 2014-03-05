<?php

class Ticket769TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base="ctl0_Content_";
		$this->url('tickets/index.php?page=Ticket769');
		$this->assertEquals($this->title(), "Verifying Ticket 769");

		$this->byId($base.'ctl0')->click();
		$this->assertVisible($base.'ctl1');

		$this->type($base.'T1', 'Prado');
		$this->byId($base.'ctl0')->click();
		$this->pause(800);
		$this->assertNotVisible($base.'ctl1');
		$this->assertValue($base.'ctl0', 'T1 clicked' );

		$this->byId($base.'ctl2')->click();
		$this->pause(800);
		$this->assertText($base.'B', 'This is B');
		$this->byId($base.'ctl3')->click();
		$this->pause(800);

		$this->type($base.'T1', '');
		$this->byId($base.'ctl0')->click();
		$this->assertVisible($base.'ctl1');
		$this->type($base.'T1', 'Prado');
		$this->byId($base.'ctl0')->click();
		$this->pause(800);
		$this->assertNotVisible($base.'ctl1');
		$this->assertValue($base.'ctl0', 'T1 clicked clicked' );
	}
}