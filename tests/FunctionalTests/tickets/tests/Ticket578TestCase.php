<?php

class Ticket578TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket578');
		$this->assertEquals("Verifying Ticket 578", $this->title());

		$this->assertText("{$base}label1", "Label 1");
		$this->byId("{$base}button1")->click();
		$this->pauseFairAmount();
		$this->assertText("{$base}label1", "Button 1 was clicked :");

		$text = "helloworld";

		$this->execute([
			'script' => "tinyMCE.get('{$base}text1').setContent('{$text}')",
			'args' => []
		]);

		$this->byId("{$base}button1")->click();
		$this->pauseFairAmount();
		$this->assertText("{$base}label1", "Button 1 was clicked : <p>{$text}</p>");
	}
}
