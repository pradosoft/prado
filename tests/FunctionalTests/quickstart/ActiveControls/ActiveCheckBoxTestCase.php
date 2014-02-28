<?php

//$Id: ActiveCheckBoxTestCase.php 3187 2012-07-12 11:21:01Z ctrlaltca $
class QuickstartActiveCheckBoxTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=ActiveControls.Samples.TActiveCheckBox.Home&amp;notheme=true&amp;lang=en");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		$this->assertTextPresent('TActiveCheckBox Samples (AJAX)');


		// an auto postback checkbox
		$this->assertTextNotPresent("ctl0_body_ctl0 clicked using callback");
		$this->click("//input[@name='ctl0\$body\$ctl0']");
		$this->pause(800);
		$this->assertChecked("//input[@name='ctl0\$body\$ctl0']");
		$this->assertTextPresent("ctl0_body_ctl0 clicked using callback");
		$this->click("//input[@name='ctl0\$body\$ctl0']");
		$this->pause(800);
		$this->assertTextPresent("ctl0_body_ctl0 clicked using callback");
		$this->assertNotChecked("//input[@name='ctl0\$body\$ctl0']");

		// a checkbox causing validation on a textbox
		$this->assertNotVisible('ctl0_body_ctl1');
		$this->click("//input[@name='ctl0\$body\$ctl2']");
		$this->assertVisible('ctl0_body_ctl1');
		$this->click("//input[@name='ctl0\$body\$ctl2']", "");
		$this->assertVisible('ctl0_body_ctl3');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->click("//input[@name='ctl0\$body\$ctl2']", "");
		$this->pause(800);
		$this->assertNotVisible('ctl0_body_ctl1');
		$this->assertTextPresent("ctl0_body_ctl2 clicked using callback");

		// a checkbox validated by a required field validator
		$this->assertNotChecked("//input[@name='ctl0\$body\$CheckBox']");
		$this->assertNotVisible('ctl0_body_ctl4');
		$this->click("//input[@type='submit' and @value='Submit']", "");
		$this->assertVisible('ctl0_body_ctl4');
		$this->click("//input[@name='ctl0\$body\$CheckBox']", "");
		$this->assertChecked("//input[@name='ctl0\$body\$CheckBox']");
		$this->click("//input[@type='submit' and @value='Submit']", "");
		$this->pause(800);
		$this->assertNotVisible('ctl0_body_ctl4');
		$this->assertTextPresent("ctl0_body_CheckBox clicked");

		// a checkbox validated by a required field validator using AutoPostBack
		$this->assertChecked("//input[@name='ctl0\$body\$CheckBox2']");
		$this->assertNotVisible('ctl0_body_ctl5');
		$this->click("//input[@name='ctl0\$body\$CheckBox2']", "");
		$this->assertVisible('ctl0_body_ctl5');
		$this->assertChecked("//input[@name='ctl0\$body\$CheckBox2']");
	}
}
