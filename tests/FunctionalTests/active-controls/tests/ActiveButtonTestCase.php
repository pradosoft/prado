<?php

class ActiveButtonTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("active-controls/index.php?page=ActiveButtonTest");
		$this->assertSourceContains("TActiveButton Functional Test");
		$this->assertText("{$base}label1", "Label 1");
		$this->clickOnElement("{$base}button2");
		$this->pauseFairAmount();
		$this->assertText("{$base}label1", "Button 1 was clicked using callback!");
	}
}
