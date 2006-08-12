<?php

class CustomTemplateTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('active-controls/index.php?page=CustomTemplateControlTest');
		$this->assertTextPresent('Add Dynamic Custom TTemplateControl Test');
		$this->assertText('label1', 'Label 1');

		$this->type('foo', 'Foo Bar!');
		$this->click('button2');
		$this->pause(800);

		$this->assertVisible('ctl1_ThePanel');
		$this->assertTextPresent('Client ID: ctl1_ThePanel');

		$this->assertText('label1', 'Button 1 was clicked Foo Bar! using callback!... and this is the textbox text: Foo Bar!');
	}
}

?>