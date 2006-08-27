<?php

class TimeTriggeredCallbackTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=TimeTriggeredCallbackTest");
		$this->verifyTextPresent("TimeTriggeredCallback + ViewState Tests");

		$this->assertText("label1", "ViewState Counter :");

		$this->click("button1");

		$this->pause(8000);

		$this->assertText("label1", "ViewState Counter : 1 2 3 4 5 6 7 8 9 10");

	}
}

?>