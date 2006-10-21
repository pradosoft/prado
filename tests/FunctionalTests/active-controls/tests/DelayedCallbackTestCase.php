<?php

class DelayedCallbackTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=DelayedCallback");
		$this->verifyTextPresent("Delayed Callback Test");

		$this->assertText("status", "");
		$this->click("button1");
		$this->click("button2");

		$this->pause("5000");
		$this->assertText("status", "Callback 1 returned after 4s");
		$this->pause("3000");
		$this->assertText("status", "Callback 2 delayed 2s");

	}
}

?>