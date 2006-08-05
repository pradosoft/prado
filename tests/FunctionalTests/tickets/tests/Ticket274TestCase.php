<?php

class Ticket274TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket274');
		$this->assertTitle('Verifying Ticket 274');
		$this->assertNotVisible($base.'validator1');
		$this->assertNotVisible($base.'validator2');
		
		$this->click($base.'button1');		
		$this->assertVisible($base.'validator1');
		$this->assertNotVisible($base.'validator2');
		
		$this->type($base.'MyDate', 'asd');
		$this->click($base.'button1');
		$this->assertVisible($base.'validator1');
		$this->assertNotVisible($base.'validator2');		
	}
}

?>