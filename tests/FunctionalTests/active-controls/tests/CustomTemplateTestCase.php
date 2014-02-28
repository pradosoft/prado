<?php

class CustomTemplateTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url('active-controls/index.php?page=CustomTemplateControlTest');
		$this->assertTextPresent('Add Dynamic Custom TTemplateControl Test');
		$this->assertText("{$base}label1", 'Label 1');

		$this->type("{$base}foo", 'Foo Bar!');
		$this->click("{$base}button2");
		$this->pause(800);

		$this->assertVisible("{$base}ctl0_ThePanel");
		$this->assertTextPresent("Client ID: {$base}ctl0_ThePanel");

		$this->assertText("{$base}label1", 'Button 1 was clicked Foo Bar! using callback!... and this is the textbox text: Foo Bar!');
	}
}
