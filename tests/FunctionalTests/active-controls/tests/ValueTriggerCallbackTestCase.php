<?php

class ValueTriggerTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=ValueTriggerCallbackTest");
		$this->verifyTextPresent("Value Trigger Callback Test");

		$this->assertText('label1', 'Label 1');

		$this->type('text1', 'test');
		$this->pause(2000);
		$this->assertText('label1', 'Old = : New Value = test');

		$this->type('text1', 'more');
		$this->pause(3000);
		$this->assertText('label1', 'Old = test : New Value = more');
	}
}

?>