<?php

class ActiveHyperLinkTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=ActiveHyperLinkTest");
		$this->assertTextPresent("Active HyperLink Test Case");
		
		$this->assertText("link1", "Link 1");
		
		$this->click("button1");
		$this->pause(500);
		$this->assertText("link1", "Pradosoft.com");
	}
}

?>