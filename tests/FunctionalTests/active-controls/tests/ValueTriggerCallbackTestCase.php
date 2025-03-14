<?php

class ValueTriggerCallbackTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("active-controls/index.php?page=ValueTriggerCallbackTest");
		$this->assertSourceContains("Value Trigger Callback Test");

		$this->assertText("{$base}label1", 'Label 1');

		$this->type("{$base}text1", 'test');
		$this->pause(3000);
		$this->assertText("{$base}label1", 'Old = : New Value = test');

		$this->type("{$base}text1", 'more');
		$this->pause(3000);
		$this->assertText("{$base}label1", 'Old = test : New Value = more');
	}
}
