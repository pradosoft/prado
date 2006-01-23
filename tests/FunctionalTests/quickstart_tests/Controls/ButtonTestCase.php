<?php

class ButtonTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TButton.Home", "");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// a regular button
		$this->clickAndWait("//input[@type='submit' and @value='text']", "");

		// a click button
		$this->verifyElementNotPresent("//input[@type='submit' and @value=\"I'm clicked\"]");
		$this->clickAndWait("//input[@type='submit' and @value='click me']", "");
		$this->verifyElementPresent("//input[@type='submit' and @value=\"I'm clicked\"]");

		// a command button
		$this->verifyElementNotPresent("//input[@type='submit' and @value=\"Name: test, Param: value\"]");
		$this->clickAndWait("//input[@type='submit' and @value='click me']", "");
		$this->verifyElementPresent("//input[@type='submit' and @value=\"Name: test, Param: value\"]");

		// a button causing validation
		$this->verifyNotVisible('ctl0_body_ctl3');
		$this->click("//input[@type='submit' and @value='submit']", "");
		$this->pause(1000);
		$this->verifyVisible('ctl0_body_ctl3');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->clickAndWait("//input[@type='submit' and @value='submit']", "");
		$this->verifyNotVisible('ctl0_body_ctl3');
	}
}

?>