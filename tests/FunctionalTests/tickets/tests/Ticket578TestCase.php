<?php

class Ticket578TestCase extends SeleniumTestCase
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

		$this->store($this->setTinymceHtml("{$base}text1", "helloworld"),"t2");
		$this->click("{$base}button1", "");
		$this->pause(800);
		$this->assertText("{$base}label1", "Button 1 was clicked : helloworld");
	}

	function setTinymceHtml($id, $text)
	{
		$tinymce = "this.browserbot.getCurrentWindow().tinyMCE.getInstanceById('{$id}')";
		return 'javascript{'."{$tinymce}.setHTML('{$text}') ? 0 : 1".'}';
	}
}

?>