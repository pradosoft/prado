<?php

class ActiveLinkButtonTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=ActiveLinkButtonTest");
		$this->assertSourceContains("TActiveLinkButton Functional Test");
		$this->assertText("{$base}label1", "Label 1");
		$this->byId("{$base}button2")->click();
		$this->pause(800);
		$this->assertText("{$base}label1", "Button 1 was clicked using callback!");
	}
}
