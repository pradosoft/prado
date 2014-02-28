<?php
class Ticket708TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket708');
		$this->assertEquals($this->title(), "Verifying Ticket 708");

		$this->click($base."grid_ctl1_RadioButton");
		$this->pause(800);
		$this->assertText($base."Result", "You have selected Radio Button #1");

		$this->click($base."grid_ctl2_RadioButton");
		$this->pause(800);
		$this->assertText($base."Result", "You have selected Radio Button #2");

		$this->click($base."grid_ctl3_RadioButton");
		$this->pause(800);
		$this->assertText($base."Result", "You have selected Radio Button #3");

		$this->click($base."grid_ctl4_RadioButton");
		$this->pause(800);
		$this->assertText($base."Result", "You have selected Radio Button #4");
	}

}