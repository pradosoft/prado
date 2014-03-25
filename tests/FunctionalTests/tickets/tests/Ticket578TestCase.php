<?php

class Ticket578TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket578');
		$this->assertEquals("Verifying Ticket 578", $this->title());

		$this->assertText("{$base}label1", "Label 1");
		$this->byId("{$base}button1")->click();
		$this->pause(800);
		$this->assertText("{$base}label1", "Button 1 was clicked :");

		$text="helloworld";

		$this->execute(array(
			'script' => "tinyMCE.get('{$base}text1').setContent('{$text}')",
			'args'   => array()
		));

		$this->byId("{$base}button1")->click();
		$this->pause(800);
		$this->assertText("{$base}label1", "Button 1 was clicked : <p>{$text}</p>");
	}
}
