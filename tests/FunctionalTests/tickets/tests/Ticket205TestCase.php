<?php

class Ticket205TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open("tickets/index.php?page=Ticket205");
		$this->assertTitle("Verifying Ticket 205");
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

?>