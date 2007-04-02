<?php

/**
* 
*/
class Ticket290TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket290');
		$this->assertTitle("Verifying Ticket 290");
		
		$this->assertText("{$base}label1", "Label 1");
		$this->assertText("{$base}label2", "Label 2");
		
		$this->type("{$base}textbox1", "test");
		$this->keyDownAndWait("{$base}textbox1", "\\13");
		
		$this->assertText("{$base}label1", "Doing Validation");
		$this->assertText("{$base}label2", "Button 2 (default) Clicked!");
	}
}


?>