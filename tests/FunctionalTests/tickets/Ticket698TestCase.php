<?php

class Ticket698TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket698');
		$this->assertTitle("Verifying Ticket 698");

		$this->byId($base . "switchContentTypeButton")->click();
		$this->assertVisible($base . "EditHtmlTextBox");
		$this->byId($base . "switchContentTypeButton")->click();
		$this->pause(1000);
		$this->assertNotVisible($base . "EditHtmlTextBox");
	}
}
