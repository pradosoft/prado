<?php

class CallbackOptionsTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=CallbackOptionsTest");
		$this->verifyTextPresent("TCallbackOptions Test");

		$this->assertText("label1", "Label 1");
		$this->assertText("label2", "Label 2");
		$this->assertText("label3", "Label 3");

		$this->click("button1");
		$this->pause(800);
		$this->assertText("label1", "Button 1 has returned");
		$this->assertText("label2", "Label 2");
		$this->assertText("label3", "Label 3");

		$this->click("button2");
		$this->pause(800);
		$this->assertText("label1", "Button 1 has returned");
		$this->assertText("label2", "Button 2 has returned");
		$this->assertText("label3", "Label 3");

		$this->click("button3");
		$this->pause(800);
		$this->assertText("label1", "Button 1 has returned");
		$this->assertText("label2", "Button 2 has returned");
		$this->assertText("label3", "Button 3 has returned");
	}
}

?>