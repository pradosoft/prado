<?php

class Ticket671_reopenedTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url('tickets/index.php?page=Ticket671_reopened');
		$this->assertEquals($this->title(), "Verifying Ticket 671_reopened");
		// Type wrong value
		$this->type($base . 'testField', 'abcd');
		$this->byId($base . 'ctl4')->click();
		$this->pauseFairAmount();
		$this->assertVisible($base . 'ctl2');
		$this->assertText($base . 'Result', 'Check callback called (1) --- Save callback called DATA NOK');

		// Reclick, should not have any callback
		$this->byId($base . 'ctl4')->click();
		$this->pauseFairAmount();
		$this->assertVisible($base . 'ctl2');
		$this->assertText($base . 'Result', 'Check callback called (2) --- Save callback called DATA NOK');

		// Type right value
		$this->type($base . 'testField', 'Test');
		$this->byId($base . 'ctl4')->click();
		$this->pauseFairAmount();
		$this->assertNotVisible($base . 'ctl2');
		$this->assertText($base . 'Result', 'Check callback called (3) --- Save callback called DATA OK');

		// Type empty value
		$this->type($base . 'testField', '');
		$this->byId($base . 'ctl4')->click();
		$this->pauseFairAmount();
		$this->assertVisible($base . 'ctl1');
		$this->assertNotVisible($base . 'ctl2');
		$this->assertText($base . 'Result', 'Check callback called (3) --- Save callback called DATA OK');

		// Type right value
		$this->type($base . 'testField', 'Test');
		$this->byId($base . 'ctl4')->click();
		$this->pauseFairAmount();
		$this->assertNotVisible($base . 'ctl1');
		$this->assertNotVisible($base . 'ctl2');
		$this->assertText($base . 'Result', 'Check callback called (4) --- Save callback called DATA OK');
	}
}
