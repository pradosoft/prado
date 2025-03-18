<?php

class Ticket595TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url('tickets/index.php?page=Ticket595');
		$this->assertTitle("Verifying Ticket 595");

		$this->click($base . 'ctl2');
		$this->assertAttribute($base . 'A@class', 'errorclassA');

		$this->type($base . 'A', 'Prado');
		$this->click($base . 'ctl2');
		$this->assertAttribute($base . 'A@class', 'errorclassA');

		$this->type($base . 'A', 'test@prado.local');
		$this->click($base . 'ctl2');
		$this->assertAttribute($base . 'A@class', '');

		$this->click($base . 'ctl5');
		$this->assertAttribute($base . 'B@class', ' errorclassB');

		$this->type($base . 'B', 'Prado');
		$this->click($base . 'ctl5');
		$this->assertAttribute($base . 'B@class', ' errorclassB');

		$this->pause(50);
		$this->type($base . 'B', 'test@prado.local');
		$this->click($base . 'ctl5');
		$this->assertAttribute($base . 'B@class', '');
	}
}
