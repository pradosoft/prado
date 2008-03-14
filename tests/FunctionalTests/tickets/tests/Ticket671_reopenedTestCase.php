<?php

class Ticket671_reopenedTestCase extends SeleniumTestCase
{
	function test()
	{
		$base="ctl0_Content_";
		$this->open('tickets/index.php?page=Ticket671_reopened');
		$this->assertTitle("Verifying Ticket 671_reopened");
		// Type wrong value
		$this->type($base.'testField', 'abcd');
		$this->click($base.'ctl4');
		$this->pause(800);
		$this->assertVisible($base.'ctl2');
		$this->assertText($base.'Result', 'Check callback called (1)');
		
		// Reclick, should not have any callback
		$this->click($base.'ctl4');
		$this->pause(800);
		$this->assertVisible($base.'ctl2');
		$this->assertText($base.'Result', 'Check callback called (1)');
		
		// Type right value
		$this->type($base.'testField', 'Test');
		$this->click($base.'ctl4');
		$this->pause(800);
		$this->assertNotVisible($base.'ctl2');
		// The check method is called twice. Once by request on clientside, once on server side when callback request is issued.
		$this->assertText($base.'Result', 'Check callback called (3) --- Save callback called DATA OK');
		
		// Type empty value
		$this->type($base.'testField', '');
		$this->click($base.'ctl4');
		$this->pause(800);
		$this->assertVisible($base.'ctl1');
		$this->assertVisible($base.'ctl2');
		$this->assertText($base.'Result', 'Check callback called (4)');
		
		// Type right value
		$this->type($base.'testField', 'Test');
		$this->click($base.'ctl4');
		$this->pause(800);
		$this->assertNotVisible($base.'ctl1');
		$this->assertNotVisible($base.'ctl2');
		// The check method is called twice. Once by request on clientside, once on server side when callback request is issued.
		$this->assertText($base.'Result', 'Check callback called (6) --- Save callback called DATA OK');
		
	}
}
?>