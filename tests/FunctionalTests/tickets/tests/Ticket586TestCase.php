<?php

class Ticket586TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket586');
		$this->assertTitle("Verifying Ticket 586");

		$this->assertText("{$base}label1", "Status");
		$this->byId("{$base}button1")->click();
		$this->pause(50);
		$this->assertText("{$base}label1", "Button 1 Clicked!");

		$this->type("{$base}text1", "testing");

		// this can't work properly without manual testing
		// $this->keyDownAndWait("{$base}text1", '\13');
		// $this->assertText("{$base}label1", "Button 2 (default) Clicked!");
	}
}
