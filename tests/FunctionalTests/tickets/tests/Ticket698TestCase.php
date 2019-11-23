<?php

class Ticket698TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket698');
		$this->assertEquals($this->title(), "Verifying Ticket 698");

		$this->byId($base . "switchContentTypeButton")->click();
		$this->pauseFairAmount();
		$this->assertVisible($base . "EditHtmlTextBox");
		$this->pauseFairAmount();
		$this->byId($base . "switchContentTypeButton")->click();
		$this->pause(1000);
		$this->assertNotVisible($base . "EditHtmlTextBox");
	}
}
