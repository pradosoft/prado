<?php

class QuickstartButtonTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TButton.Home&amp;notheme=true&amp;lang=en");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// a regular button
		$this->clickAndWait("//input[@type='submit' and @value='text']", "");

		// a click button
		$this->assertElementNotPresent("//input[@type='submit' and @value=\"I'm clicked\"]");
		$this->clickAndWait("//input[@type='submit' and @value='click me']", "");
		$this->assertElementPresent("//input[@type='submit' and @value=\"I'm clicked\"]");

		// a command button
		$this->assertElementNotPresent("//input[@type='submit' and @value=\"Name: test, Param: value\"]");
		$this->clickAndWait("//input[@type='submit' and @value='click me']", "");
		$this->assertElementPresent("//input[@type='submit' and @value=\"Name: test, Param: value\"]");

		// a button causing validation
		$this->assertNotVisible('ctl0_body_ctl3');
		$this->click("//input[@type='submit' and @value='submit']", "");
//		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl3');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->clickAndWait("//input[@type='submit' and @value='submit']", "");
		$this->assertNotVisible('ctl0_body_ctl3');
	}
}
