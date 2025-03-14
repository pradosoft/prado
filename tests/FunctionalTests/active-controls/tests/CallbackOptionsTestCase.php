<?php

class CallbackOptionsTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("active-controls/index.php?page=CallbackOptionsTest");
		$this->assertSourceContains("TCallbackOptions Test");

		$this->assertText("label1", "Label 1");
		$this->assertText("label2", "Label 2");
		$this->assertText("label3", "Label 3");

		$this->byId("{$base}button1")->click();
		$this->assertText("label1", "Button 1 has returned");
		$this->assertText("label2", "Label 2");
		$this->assertText("label3", "Label 3");

		$this->byId("{$base}button2")->click();
		$this->assertText("label1", "Button 1 has returned");
		$this->assertText("label2", "Button 2 has returned");
		$this->assertText("label3", "Label 3");

		$this->byId("{$base}button3")->click();
		$this->assertText("label1", "Button 1 has returned");
		$this->assertText("label2", "Button 2 has returned");
		$this->assertText("label3", "Button 3 has returned");
	}
}
