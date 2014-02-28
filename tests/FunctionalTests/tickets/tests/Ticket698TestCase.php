<?php
class Ticket698TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket698');
		$this->assertEquals($this->title(), "Verifying Ticket 698");

		$this->click($base."switchContentTypeButton");
		$this->pause(800);
		$this->assertVisible($base."EditHtmlTextBox");
		$this->pause(800);
		$this->click($base."switchContentTypeButton");
		$this->pause(1000);
		$this->assertNotVisible($base."EditHtmlTextBox");
	}

}