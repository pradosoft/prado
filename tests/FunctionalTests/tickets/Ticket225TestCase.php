<?php

class Ticket225TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url('tickets/index.php?page=Ticket225');
		$this->assertSourceContains('RadioButton Group Tests');
		$this->assertText("{$base}label1", "Label 1");

		$this->assertNotVisible("{$base}validator1");
		$this->byId("{$base}button4")->click();
		$this->assertVisible("{$base}validator1");

		$this->byId("{$base}button2")->click();
		$this->byId("{$base}button4")->click();

		$this->assertNotVisible("{$base}validator1");
		$this->assertText("{$base}label1", 'ctl0$Content$button1 ctl0$Content$button2 ctl0$Content$button3');
	}
}
