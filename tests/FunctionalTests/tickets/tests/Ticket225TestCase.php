<?php

class Ticket225TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base="ctl0_Content_";
		$this->url('tickets/index.php?page=Ticket225');
		$this->assertContains('RadioButton Group Tests', $this->source());
		$this->assertText("{$base}label1", "Label 1");

		$this->assertNotVisible("{$base}validator1");
		$this->byId("{$base}button4")->click();
		$this->assertVisible("{$base}validator1");

		$this->byId("{$base}button2")->click();
		$this->byId("{$base}button4")->click();

		$this->assertText("{$base}label1", 'ctl0$Content$button1 ctl0$Content$button2 ctl0$Content$button3');
		$this->assertNotVisible("{$base}validator1");
	}
}
