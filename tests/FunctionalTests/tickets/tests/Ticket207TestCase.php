<?php

class Ticket207TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket207');
		$this->assertEquals($this->title(), "Verifying Ticket 207");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");

		$this->click("{$base}button1");
		$this->assertAlert('error on text1 fired');
		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");

		$this->type("{$base}text1", 'test');
		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");

		$this->click("{$base}button1");
		$this->assertNotVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");

		$this->type("{$base}text1", '');
		$this->assertNotVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");

		$this->click("{$base}button1");
		$this->assertAlert('error on text1 fired');
		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
	}
}
