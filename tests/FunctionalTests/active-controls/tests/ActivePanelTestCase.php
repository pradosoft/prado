<?php

class ActivePanelTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("active-controls/index.php?page=ActivePanelTest");
		$this->assertSourceContains("Active Panel replacement tests");
		$this->assertSourceNotContains('Something lalala');
		$this->byId("div1")->click();
		$this->assertSourceContains("Something lalala");
	}
}
