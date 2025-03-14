<?php

class Ticket578TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket578');
		$this->assertTitle("Verifying Ticket 578");

		$this->assertText("{$base}label1", "Label 1");
		$this->byId("{$base}button1")->click();
		$this->assertText("{$base}label1", "Button 1 was clicked :");

		$text = "helloworld";

		$this->executeScript(
			"tinyMCE.get(arguments[0]).setContent(arguments[1])",
			["{$base}text1", $text]
		);

		$this->byId("{$base}button1")->click();
		$this->assertText("{$base}label1", "Button 1 was clicked : <p>{$text}</p>");
	}
}
