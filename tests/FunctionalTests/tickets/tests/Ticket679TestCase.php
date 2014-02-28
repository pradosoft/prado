<?php
class Ticket679TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket679');
		$this->assertEquals($this->title(), "Verifying Ticket 679");

		// First part of ticket : Repeater bug
		$this->click($base."ctl0");
		$this->pause(800);
		$this->assertText($base."myLabel",'outside');
		$this->assertVisible($base."myLabel");

		// Reload completly the page
		$this->refresh();
		$this->pause(800);

		$this->click($base."Repeater_ctl0_ctl0");
		$this->pause(800);
		$this->assertText($base."myLabel",'inside');
		$this->assertVisible($base."myLabel");

		// Second part of ticket : ARB bug
		$this->assertNotChecked($base."myRadioButton");
		$this->click($base."ctl1");
		$this->pause(800);
		$this->assertChecked($base."myRadioButton");
		$this->click($base."ctl2");
		$this->pause(800);
		$this->assertNotChecked($base."myRadioButton");
		$this->pause(800);
	}

}