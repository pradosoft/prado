<?php

class Ticket679TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket679');
		$this->assertTitle("Verifying Ticket 679");

		// First part of ticket : Repeater bug
		$this->byId($base . "ctl0")->click();
		$this->assertText($base . "myLabel", 'outside');
		$this->assertVisible($base . "myLabel");

		// Reload completly the page
		$this->refresh();

		$this->byId($base . "Repeater_ctl0_ctl0")->click();
		$this->assertText($base . "myLabel", 'inside');
		$this->assertVisible($base . "myLabel");

		// Second part of ticket : ARB bug
		$this->assertNotChecked("{$base}myRadioButton");
		$this->byId($base . "ctl1")->click();
		$this->assertChecked("{$base}myRadioButton");
		$this->byId($base . "ctl2")->click();
		$this->assertNotChecked("{$base}myRadioButton");
	}
}
