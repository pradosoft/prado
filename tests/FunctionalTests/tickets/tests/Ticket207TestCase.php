<?php

class Ticket207TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket207');
		$this->assertEquals($this->title(), "Verifying Ticket 207");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");

		$this->byId("{$base}button1")->click();
		$this->pause(50);

		$this->assertEquals('error on text1 fired', $this->alertText());
		$this->acceptAlert();

		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");

		$this->type("{$base}text1", 'test');
		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");

		$this->byId("{$base}button1")->click();
		$this->assertNotVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");

		$this->type("{$base}text1", '');
		$this->assertNotVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");

		$this->byId("{$base}button1")->click();
		$this->pause(50);

		$this->assertEquals('error on text1 fired', $this->alertText());
		$this->acceptAlert();

		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
	}
}
