<?php

class Ticket521TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("tickets/index.php?page=Ticket521");
		$this->assertEquals($this->title(), "Verifying Ticket 521");
		$this->assertText("{$base}label1", "Label 1");

		$this->byId("{$base}button1")->click();
		$this->pause(1200);

		$this->assertText("{$base}label1", "Button 1 was clicked on callback");
	}
}
