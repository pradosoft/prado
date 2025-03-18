<?php

class Ticket585TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket585');
		$this->assertTitle("Verifying Ticket 585");

		$this->assertText("error", "");
		$this->assertNotVisible("{$base}validator1");

		$this->byId("{$base}button1")->click();
		$this->assertText("error", "Success");
		$this->assertNotVisible("{$base}validator1");

		$this->type("{$base}test", "15-03-2007");
		$this->byId("{$base}button1")->click();
		$this->assertText("error", "Error");
		$this->assertVisible("{$base}validator1");
	}
}
