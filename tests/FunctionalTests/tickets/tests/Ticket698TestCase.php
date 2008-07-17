<?php
class Ticket698TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket698');
		$this->assertTitle("Verifying Ticket 698");
		
		$this->click($base."switchContentTypeButton");
		$this->pause(800);
		$this->assertVisible($base."EditHtmlTextBox");
		$this->pause(800);
		$this->click($base."switchContentTypeButton");
		$this->pause(1000);
		$this->assertNotVisible($base."EditHtmlTextBox");
	}

}
?>