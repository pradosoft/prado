<?php

class Ticket225TestCase extends SeleniumTestCase
{
	function test()
	{
		$base="ctl0_Content_";
		$this->open('tickets/index.php?page=Ticket225');
		$this->assertTextPresent('RadioButton Group Tests');
		$this->assertText("{$base}label1", "Label 1");
		
		$this->assertNotVisible("{$base}validator1");
		$this->click("{$base}button4");
		$this->assertVisible("{$base}validator1");
		
		$this->click("{$base}button2");
		$this->clickAndWait("{$base}button4");
		
		$this->assertText("{$base}label1", 'ctl0$Content$button1 ctl0$Content$button2 ctl0$Content$button3');
		$this->assertNotVisible("{$base}validator1");
	}
}

?>