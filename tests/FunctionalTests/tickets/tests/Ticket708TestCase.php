<?php

class Ticket708TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket708');
		$this->assertTitle("Verifying Ticket 708");

		$this->byId($base . "grid_ctl1_RadioButton")->click();
		$this->assertText($base . "Result", "You have selected Radio Button #1");

		$this->byId($base . "grid_ctl2_RadioButton")->click();
		$this->assertText($base . "Result", "You have selected Radio Button #2");

		$this->byId($base . "grid_ctl3_RadioButton")->click();
		$this->assertText($base . "Result", "You have selected Radio Button #3");

		$this->byId($base . "grid_ctl4_RadioButton")->click();
		$this->assertText($base . "Result", "You have selected Radio Button #4");
	}
}
