<?php

class CheckBoxTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TCheckBox.Home", "");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// a regular checkbox
		$this->click("//input[@name='ctl0\$body\$ctl0' and @value='ctl0\$body\$ctl0']", "");

		// a checkbox with customized value
		$this->click("//input[@name='ctl0\$body\$ctl1' and @value='value']", "");

		// an auto postback checkbox
		$this->verifyTextNotPresent("I'm clicked");
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl2' and @value='ctl0\$body\$ctl2']", "");
		$this->verifyTextPresent("I'm clicked");
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl2' and @value='ctl0\$body\$ctl2']", "");
		$this->verifyTextPresent("I'm clicked");

		// a checkbox causing validation on a textbox
		$this->verifyNotVisible('ctl0_body_ctl3');
		$this->click("//input[@name='ctl0\$body\$ctl4' and @value='ctl0\$body\$ctl4']", "");
		$this->pause(1000);
		$this->verifyVisible('ctl0_body_ctl3');
		$this->click("//input[@name='ctl0\$body\$ctl4' and @value='ctl0\$body\$ctl4']", "");
		$this->pause(1000);
		$this->verifyVisible('ctl0_body_ctl3');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl4' and @value='ctl0\$body\$ctl4']", "");
		$this->verifyNotVisible('ctl0_body_ctl3');

		// a checkbox validated by a required field validator
		$this->verifyNotVisible('ctl0_body_ctl6');
		$this->click("//input[@type='submit' and @value='Submit']", "");
		$this->pause(1000);
		$this->verifyVisible('ctl0_body_ctl6');
		$this->click("//input[@name='ctl0\$body\$CheckBox' and @value='CheckBox']", "");
		$this->clickAndWait("//input[@type='submit' and @value='Submit']", "");
		$this->verifyNotVisible('ctl0_body_ctl6');

		// a checkbox validated by a required field validator using AutoPostBack
		$this->verifyNotVisible('ctl0_body_ctl7');
		$this->click("//input[@name='ctl0\$body\$CheckBox2' and @value='CheckBox2']", "");
		$this->pause(1000);
		$this->verifyVisible('ctl0_body_ctl7');
		$this->clickAndWait("//input[@name='ctl0\$body\$CheckBox2' and @value='CheckBox2']", "");
		$this->verifyNotVisible('ctl0_body_ctl7');
	}
}

?>