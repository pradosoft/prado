<?php
class Ticket679TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket679');
		$this->assertTitle("Verifying Ticket 679");
		
		// First part of ticket : Repeater bug
		$this->click($base."ctl0");
		$this->pause(800);
		$this->assertText($base."myLabel",'outside');
		$this->verifyVisible($base."myLabel");
		
		// Reload completly the page
		$this->refresh();
		$this->pause(800);

		$this->click($base."Repeater_ctl0_ctl0");
		$this->pause(800);
		$this->assertText($base."myLabel",'inside');
		$this->verifyVisible($base."myLabel");
		
		// Second part of ticket : ARB bug
		$this->verifyNotChecked($base."myRadioButton");
		$this->click($base."ctl1");
		$this->pause(800);
		$this->verifyChecked($base."myRadioButton");
		$this->click($base."ctl2");
		$this->pause(800);
		$this->verifyNotChecked($base."myRadioButton");
		$this->pause(800);
	}

}
?>