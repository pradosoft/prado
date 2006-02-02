<?php

class RadioButtonTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TRadioButton.Home&functionaltest=true", "");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// a regular radiobutton
		$this->click("//input[@name='ctl0\$body\$ctl0' and @value='ctl0\$body\$ctl0']", "");

		// a radiobutton with customized value
		$this->click("//input[@name='ctl0\$body\$ctl1' and @value='value']", "");

		// an auto postback radiobutton
		$this->verifyTextNotPresent("I'm clicked");
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl2' and @value='ctl0\$body\$ctl2']", "");
		$this->verifyTextPresent("I'm clicked");
		$this->click("//input[@name='ctl0\$body\$ctl2' and @value='ctl0\$body\$ctl2']", "");
		$this->verifyTextPresent("I'm clicked");

		// a radiobutton causing validation on a textbox
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

		// a radiobutton validated by a required field validator
		$this->verifyNotVisible('ctl0_body_ctl6');
		$this->click("//input[@type='submit' and @value='Submit']", "");
		$this->pause(1000);
		$this->verifyVisible('ctl0_body_ctl6');
		$this->click("//input[@name='ctl0\$body\$RadioButton' and @value='ctl0\$body\$RadioButton']", "");
		$this->clickAndWait("//input[@type='submit' and @value='Submit']", "");
		$this->verifyNotVisible('ctl0_body_ctl6');

		// a radiobutton group
		$this->clickAndWait("name=ctl0\$body\$ctl7", "");
		$this->verifyTextPresent("Your selection is empty");
		$this->click("//input[@name='ctl0\$body\$RadioGroup' and @value='ctl0\$body\$Radio2']", "");
		$this->clickAndWait("name=ctl0\$body\$ctl7", "");
		$this->verifyTextPresent("Your selection is 2");
		$this->click("//input[@name='ctl0\$body\$RadioGroup' and @value='ctl0\$body\$Radio3']", "");
		$this->click("//input[@name='ctl0\$body\$Radio4' and @value='ctl0\$body\$Radio4']", "");
		$this->clickAndWait("name=ctl0\$body\$ctl7", "");
		$this->verifyTextPresent("Your selection is 34");
	}
}

?>