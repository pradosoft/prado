<?php

class DelayedCallbackTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=DelayedCallback");
		$this->assertContains("Delayed Callback Test", $this->source());

		$this->assertText("{$base}status", "");
		$this->byId("{$base}button1")->click();
		$this->byId("{$base}button2")->click();

		$this->pause("5000");
		$this->assertText("{$base}status", "Callback 1 returned after 4s");
		$this->pause("3000");
		$this->assertText("{$base}status", "Callback 2 delayed 2s");

	}
}
