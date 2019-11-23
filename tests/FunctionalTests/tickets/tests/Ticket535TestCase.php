<?php

class Ticket535TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket535');
		$this->assertEquals($this->title(), "Verifying Ticket 535");

		$this->assertText("{$base}label1", "Label 1");

		$this->byId("{$base}radio1")->click();
		$this->byId("{$base}button1")->click();
		$this->pauseFairAmount();
		$this->assertText("{$base}label1", 'radio1 checked:{1} radio2 checked:{}');

		$this->byId("{$base}radio2")->click();
		$this->byId("{$base}button1")->click();
		$this->pauseFairAmount();
		$this->assertText("{$base}label1", 'radio1 checked:{1} radio2 checked:{1}');

		$this->byId("{$base}bad_radio1")->click();
		$this->byId("{$base}button2")->click();
		$this->pauseFairAmount();
		$this->assertText("{$base}label1", 'bad_radio1 checked:{1} bad_radio2 checked:{}');

		$this->byId("{$base}bad_radio2")->click();
		$this->byId("{$base}button2")->click();
		$this->pauseFairAmount();
		$this->assertText("{$base}label1", 'bad_radio1 checked:{} bad_radio2 checked:{1}');
	}
}
