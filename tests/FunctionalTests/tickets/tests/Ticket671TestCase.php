<?php

class Ticket671TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket671');
		$this->assertEquals($this->title(), "Verifying Ticket 671");

		$this->assertNotVisible($base . 'ctl0');
		// Click submit
		$this->byId($base . 'ctl1')->click();
		$this->pauseFairAmount();
		$this->assertText($base . 'ctl0', 'Please Select Test 3');
		$this->assertVisible($base . 'ctl0');
		$this->select($base . 'addl', 'Test 2');
		$this->pauseFairAmount();
		$this->assertVisible($base . 'ctl0');
		$this->assertText($base . "lblResult", "You have selected 'Test 2'. But this is not valid !");
		$this->select($base . 'addl', 'Test 3');
		$this->pauseFairAmount();
		$this->assertNotVisible($base . 'ctl0');
		$this->assertText($base . "lblResult", "You have selected 'Test 3'.");
		$this->byId($base . 'ctl1')->click();
		$this->pauseFairAmount();
		$this->assertText($base . "lblResult", "You have successfully validated the form");

		$this->type($base . 'testTextBox', 'test');
		$this->pauseFairAmount();
		$this->byId($base . 'ctl3')->click();
		$this->pauseFairAmount();
		$this->assertVisible($base . 'ctl2');
		$this->type($base . 'testTextBox', "Prado");
		$this->pauseFairAmount();
		$this->byId($base . 'ctl3')->click();
		$this->pauseFairAmount();
		$this->assertNotVisible($base . 'ctl2');
		$this->assertText($base . 'lblResult2', 'Thanks !');
	}
}
