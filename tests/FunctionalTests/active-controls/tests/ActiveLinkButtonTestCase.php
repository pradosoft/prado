<?php

class ActiveLinkButtonTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=ActiveLinkButtonTest");
		$this->verifyTextPresent("TActiveLinkButton Functional Test");
		$this->assertText("label1", "Label 1");
		$this->click("button2");
		$this->pause(800);
		$this->assertText("label1", "Button 1 was clicked using callback!");
	}
}

?>