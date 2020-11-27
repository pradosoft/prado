<?php

/**
 *
 */
class Ticket290TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket290');
		$this->assertEquals($this->title(), "Verifying Ticket 290");

		$this->assertText("{$base}label1", "Label 1");
		$this->assertText("{$base}label2", "Label 2");

		$this->type("{$base}textbox1", "test");

		$this->byId("{$base}textbox1")->click();
		$this->keys(\PHPUnit\Extensions\Selenium2TestCase\Keys::ENTER);
		$this->pauseFairAmount();

		$this->assertText("{$base}label1", "Doing Validation");
		$this->assertText("{$base}label2", "Button 2 (default) Clicked!");
	}
}
