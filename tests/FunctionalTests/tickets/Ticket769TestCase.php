<?php

class Ticket769TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url('tickets/index.php?page=Ticket769');
		$this->assertTitle("Verifying Ticket 769");

		$this->byId($base . 'ctl0')->click();
		$this->assertVisible($base . 'ctl1');

		$this->type($base . 'T1', 'Prado');
		$this->byId($base . 'ctl0')->click();
		$this->assertNotVisible($base . 'ctl1');
		$this->assertValue($base . 'ctl0', 'T1 clicked');

		$this->byId($base . 'ctl2')->click();
		$this->assertText($base . 'B', 'This is B');
		$this->byId($base . 'ctl3')->click();

		$this->type($base . 'T1', '');
		$this->byId($base . 'ctl0')->click();
		$this->assertVisible($base . 'ctl1');
		$this->type($base . 'T1', 'Prado');
		$this->byId($base . 'ctl0')->click();
		$this->assertNotVisible($base . 'ctl1');
		$this->assertValue($base . 'ctl0', 'T1 clicked clicked');
	}
}
