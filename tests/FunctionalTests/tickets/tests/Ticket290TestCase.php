<?php

/**
* 
*/
class Ticket290TestCase extends PradoGenericSeleniumTest
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket290');
		$this->assertTitle("Verifying Ticket 290");
		
		$this->assertText("{$base}label1", "Label 1");
		$this->assertText("{$base}label2", "Label 2");

		$this->type("{$base}textbox1", "test");
		// bad hack to simulate enter key.. 
		$this->submit('ctl0_ctl1');
		$this->pause(800);
		
		$this->assertText("{$base}label1", "Doing Validation");

		// this can't work properly without manual testing
		//$this->assertText("{$base}label2", "Button 2 (default) Clicked!");
	}
}

