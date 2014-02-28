<?php

class Ticket220TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base="ctl0_Content_";
		$this->url('tickets/index.php?page=Ticket220');
		$this->assertTextPresent('ClientScript Test');
		$this->assertText("{$base}label1", "Label 1");

		$this->click("button1");
		$this->assertText("{$base}label1", 'Label 1: ok; ok 3?; ok 2!');
		$this->assertAlertNotPresent();
	}
}
