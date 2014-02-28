<?php

class DelayedCallbackTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=DelayedCallback");
		$this->assertTextPresent("Delayed Callback Test");

		$this->assertText("{$base}status", "");
		$this->click("{$base}button1");
		$this->click("{$base}button2");

		$this->pause("5000");
		$this->assertText("{$base}status", "Callback 1 returned after 4s");
		$this->pause("3000");
		$this->assertText("{$base}status", "Callback 2 delayed 2s");

	}
}
