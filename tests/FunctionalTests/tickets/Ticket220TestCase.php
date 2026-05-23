<?php

class Ticket220TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url('tickets/index.php?page=Ticket220');
		$this->assertSourceContains('ClientScript Test');
		$this->assertText("{$base}label1", "Label 1");

		$this->byId("button1")->click();
		$this->assertText("{$base}label1", 'Label 1: ok; ok 3?; ok 2!');
		$this->assertAlertNotPresent();
	}
}
