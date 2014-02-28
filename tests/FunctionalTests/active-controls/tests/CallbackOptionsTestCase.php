<?php

class CallbackOptionsTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=CallbackOptionsTest");
		$this->assertTextPresent("TCallbackOptions Test");

		$this->assertText("label1", "Label 1");
		$this->assertText("label2", "Label 2");
		$this->assertText("label3", "Label 3");

		$this->click("{$base}button1");
		$this->pause(800);
		$this->assertText("label1", "Button 1 has returned");
		$this->assertText("label2", "Label 2");
		$this->assertText("label3", "Label 3");

		$this->click("{$base}button2");
		$this->pause(800);
		$this->assertText("label1", "Button 1 has returned");
		$this->assertText("label2", "Button 2 has returned");
		$this->assertText("label3", "Label 3");

		$this->click("{$base}button3");
		$this->pause(800);
		$this->assertText("label1", "Button 1 has returned");
		$this->assertText("label2", "Button 2 has returned");
		$this->assertText("label3", "Button 3 has returned");
	}
}
