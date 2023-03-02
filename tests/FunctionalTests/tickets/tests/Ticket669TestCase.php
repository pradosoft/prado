<?php

class Ticket669TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket669');
		$this->assertEquals($this->title(), "Verifying Ticket 669");

		$this->assertSourceContains('1 - Test without callback');
		$this->assertValue($base . 'tb1', 'ActiveTextBox');
		$this->assertValue($base . 'tb2', 'TextBox in ActivePanel');

		$this->byId($base . 'ctl4')->click();
		$this->pauseFairAmount();
		$this->assertValue($base . 'tb1', 'ActiveTextBox +1');
		$this->assertValue($base . 'tb2', 'TextBox in ActivePanel +1');

		$this->byId($base . 'ctl1')->click();
		$this->pauseFairAmount();
		$this->assertSourceContains('2 - Test callback with 2nd ActivePanel');
		$this->assertValue($base . 'tb3', 'ActiveTextBox');
		$this->assertValue($base . 'tb4', 'TextBox in ActivePanel');
		$this->assertValue($base . 'tb5', 'TextBox in ActivePanel');

		$this->byId($base . 'ctl6')->click();
		$this->pauseFairAmount();

		$this->assertValue($base . 'tb3', 'ActiveTextBox +1');
		$this->assertValue($base . 'tb4', 'TextBox in ActivePanel +1');
		$this->assertValue($base . 'tb5', 'TextBox in ActivePanel +1');

		$this->byId($base . 'ctl2')->click();
		$this->pauseFairAmount();
		$this->assertSourceContains('3 - Test callback without 2nd ActivePanel');
		$this->assertValue($base . 'tb6', 'ActiveTextBox');
		$this->assertValue($base . 'tb7', 'TextBox in Panel');

		$this->byId($base . 'ctl8')->click();
		$this->pauseFairAmount();

		$this->assertValue($base . 'tb6', 'ActiveTextBox +1');
		$this->assertValue($base . 'tb7', 'TextBox in Panel +1');
	}
}
