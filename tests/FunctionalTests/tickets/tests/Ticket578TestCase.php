<?php

class Ticket578TestCase extends PradoGenericSeleniumTest
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket578');
		$this->verifyTitle("Verifying Ticket 578", "");

		$this->assertText("{$base}label1", "Label 1");
		$this->click("{$base}button1", "");
		$this->pause(800);
		$this->assertText("{$base}label1", "Button 1 was clicked : ");

		$text="helloworld";
		$this->runScript("tinyMCE.get('{$base}text1').setContent('{$text}')");
		$this->click("{$base}button1", "");
		$this->pause(800);
		$this->assertText("{$base}label1", "exact:Button 1 was clicked : <p>{$text}</p>");
	}
}
