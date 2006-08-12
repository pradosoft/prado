<?php

class ActivePanelTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=ActivePanelTest");
		$this->verifyTextPresent("Active Panel replacement tests");
		$this->assertTextNotPresent('Something lalala');
		$this->click("div1");
		$this->pause(800);
		$this->assertTextPresent("Something lalala");
	}
}

?>