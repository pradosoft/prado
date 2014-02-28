<?php

class QuickstartRadioButtonTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TRadioButton.Home&amp;notheme=true&amp;lang=en");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// a regular radiobutton
		$this->click("//input[@name='ctl0\$body\$ctl0' and @value='ctl0\$body\$ctl0']", "");

		// a radiobutton with customized value
		$this->click("//input[@name='ctl0\$body\$ctl1' and @value='value']", "");

		// an auto postback radiobutton
		$this->assertTextNotPresent("I'm clicked");
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl2' and @value='ctl0\$body\$ctl2']", "");
		$this->assertTextPresent("I'm clicked");
		$this->click("//input[@name='ctl0\$body\$ctl2' and @value='ctl0\$body\$ctl2']", "");
		$this->assertTextPresent("I'm clicked");

		// a radiobutton causing validation on a textbox
		$this->assertNotVisible('ctl0_body_ctl3');
		$this->click("//input[@name='ctl0\$body\$ctl4' and @value='ctl0\$body\$ctl4']", "");
		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl3');
		$this->click("//input[@name='ctl0\$body\$ctl4' and @value='ctl0\$body\$ctl4']", "");
		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl3');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl4' and @value='ctl0\$body\$ctl4']", "");
		$this->assertNotVisible('ctl0_body_ctl3');

		// a radiobutton validated by a required field validator
		$this->assertNotVisible('ctl0_body_ctl6');
		$this->click("//input[@type='submit' and @value='Submit']", "");
		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl6');
		$this->click("//input[@name='ctl0\$body\$RadioButton' and @value='ctl0\$body\$RadioButton']", "");
		$this->clickAndWait("//input[@type='submit' and @value='Submit']", "");
		$this->assertNotVisible('ctl0_body_ctl6');

		// a radiobutton group
		$this->clickAndWait("name=ctl0\$body\$ctl7", "");
		$this->assertTextPresent("Your selection is empty");
		$this->click("//input[@name='ctl0\$body\$RadioGroup' and @value='ctl0\$body\$Radio2']", "");
		$this->clickAndWait("name=ctl0\$body\$ctl7", "");
		$this->assertTextPresent("Your selection is 2");
		$this->click("//input[@name='ctl0\$body\$RadioGroup' and @value='ctl0\$body\$Radio3']", "");
		$this->click("//input[@name='ctl0\$body\$Radio4' and @value='ctl0\$body\$Radio4']", "");
		$this->clickAndWait("name=ctl0\$body\$ctl7", "");
		$this->assertTextPresent("Your selection is 34");
	}
}
