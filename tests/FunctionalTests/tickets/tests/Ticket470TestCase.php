<?php

class Ticket470TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket470');
		$this->assertTitle("Verifying Ticket 470");
		$this->assertText("{$base}counter", "0");
		$this->assertText("{$base}Results", "");
		$this->assertNotVisible("{$base}validator1");

		$this->byId("{$base}button1")->click();
		$this->assertText("{$base}counter", "0");
		$this->assertText("{$base}Results", "");
		$this->assertVisible("{$base}validator1");

		$this->type("{$base}TextBox", "hello");
		$this->byId("{$base}button1")->click();
		$this->assertText("{$base}counter", "0");
		$this->assertText("{$base}Results", "OK!!!");
		$this->assertNotVisible("{$base}validator1");

		//reload
		$this->byId("{$base}reloadButton")->click();
		$this->assertValue("{$base}TextBox", "hello");
		$this->assertText("{$base}counter", "1");
		$this->assertText("{$base}Results", "");
		$this->assertNotVisible("{$base}validator1");

		$this->type("{$base}TextBox", "");
		$this->byId("{$base}button1")->click();
		$this->assertText("{$base}counter", "1");
		$this->assertText("{$base}Results", "");
		$this->assertVisible("{$base}validator1");

		$this->type("{$base}TextBox", "test");
		$this->byId("{$base}button1")->click();
		$this->assertText("{$base}counter", "1");
		$this->assertText("{$base}Results", "OK!!!");
		$this->assertNotVisible("{$base}validator1");
	}
}
