<?php

class ActivePanelTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=ActivePanelTest");
		$this->assertContains("Active Panel replacement tests", $this->source());
		$this->assertNotContains('Something lalala', $this->source());
		$this->byId("div1")->click();
		$this->pause(800);
		$this->assertContains("Something lalala", $this->source());
	}
}
