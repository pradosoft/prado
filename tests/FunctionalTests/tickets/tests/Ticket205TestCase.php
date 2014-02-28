<?php

class Ticket205TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->url("tickets/index.php?page=Ticket205");
		$this->assertEquals($this->title(), "Verifying Ticket 205");
		$this->assertNotVisible("{$base}validator1");

		$this->type("{$base}textbox1", "test");
		$this->click("{$base}button1");
		$this->assertAlert("error");
		$this->assertVisible("{$base}validator1");

		$this->type("{$base}textbox1", "Prado");
		$this->clickAndWait("{$base}button1");
		$this->assertNotVisible("{$base}validator1");
	}
}
