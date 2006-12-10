<?php

class Ticket470TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket470');
		$this->verifyTitle("Verifying Ticket 470");
		$this->assertText("{$base}counter", "0");
		$this->assertText("{$base}Results", "");
		$this->assertNotVisible("{$base}validator1");

		$this->click("{$base}button1");
		$this->pause(800);
		$this->assertText("{$base}counter", "0");
		$this->assertText("{$base}Results", "");
		$this->assertVisible("{$base}validator1");

		$this->type("{$base}TextBox", "hello");
		$this->click("{$base}button1");
		$this->pause(800);
		$this->assertText("{$base}counter", "0");
		$this->assertText("{$base}Results", "OK!!!");
		$this->assertNotVisible("{$base}validator1");

		//reload
		$this->click("{$base}reloadButton");
		$this->pause(800);
		$this->assertValue("{$base}TextBox", "hello");
		$this->assertText("{$base}counter", "1");
		$this->assertText("{$base}Results", "");
		$this->assertNotVisible("{$base}validator1");

		$this->type("{$base}TextBox", "");
		$this->click("{$base}button1");
		$this->pause(800);
		$this->assertText("{$base}counter", "1");
		$this->assertText("{$base}Results", "");
		$this->assertVisible("{$base}validator1");
	
		$this->type("{$base}TextBox", "test");
		$this->click("{$base}button1");
		$this->pause(800);
		$this->assertText("{$base}counter", "1");
		$this->assertText("{$base}Results", "OK!!!");
		$this->assertNotVisible("{$base}validator1");
	}
}

?>