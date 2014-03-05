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
		$this->byId("{$base}button1")->click();

		$this->assertEquals("error", $this->alertText());
		$this->acceptAlert();

		$this->assertVisible("{$base}validator1");

		$this->type("{$base}textbox1", "Prado");
		$this->byId("{$base}button1")->click();
		$this->assertNotVisible("{$base}validator1");
	}
}
