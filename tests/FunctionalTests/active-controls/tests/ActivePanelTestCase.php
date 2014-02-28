<?php

class ActivePanelTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=ActivePanelTest");
		$this->assertTextPresent("Active Panel replacement tests");
		$this->assertTextNotPresent('Something lalala');
		$this->click("div1");
		$this->pause(800);
		$this->assertTextPresent("Something lalala");
	}
}
