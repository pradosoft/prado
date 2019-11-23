<?php

class Ticket595TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url('tickets/index.php?page=Ticket595');
		$this->assertEquals($this->title(), "Verifying Ticket 595");

		$this->byId($base . 'ctl2')->click();
		$this->assertAttribute($base . 'A@class', 'errorclassA');

		$this->type($base . 'A', 'Prado');
		$this->byId($base . 'ctl2')->click();
		$this->assertAttribute($base . 'A@class', 'errorclassA');

		$this->type($base . 'A', 'test@prado.local');
		$this->byId($base . 'ctl2')->click();
		$this->pauseFairAmount();
		$this->assertAttribute($base . 'A@class', '');

		$this->byId($base . 'ctl5')->click();
		$this->pauseFairAmount();
		$this->assertAttribute($base . 'B@class', ' errorclassB');

		$this->type($base . 'B', 'Prado');
		$this->byId($base . 'ctl5')->click();
		$this->pauseFairAmount();
		$this->assertAttribute($base . 'B@class', ' errorclassB');

		$this->type($base . 'B', 'test@prado.local');
		$this->byId($base . 'ctl5')->click();
		$this->pauseFairAmount();
		$this->assertAttribute($base . 'B@class', '');
	}
}
