<?php

class ActiveHyperLinkTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=ActiveHyperLinkTest");
		$this->assertTextPresent("Active HyperLink Test Case");

		$this->assertText("{$base}link1", "Link 1");

		$this->click("{$base}button1");
		$this->pause(800);
		$this->assertText("{$base}link1", "Pradosoft.com");
	}
}
