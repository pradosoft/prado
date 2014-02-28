<?php
//$Id: ActiveButtonTestCase.php 3187 2012-07-12 11:21:01Z ctrlaltca $
class QuickstartActiveButtonTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=ActiveControls.Samples.TActiveButton.Home&amp;notheme=true&amp;lang=en");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		$this->assertTextPresent('TActiveButton Samples (AJAX)');

		// a click button
		$this->assertElementNotPresent("//input[@type='submit' and @value=\"I'm clicked\"]");
		$this->click("//input[@type='submit' and @value='click me']", "");
		$this->pause(800);
		$this->assertElementPresent("//input[@type='submit' and @value=\"I'm clicked\"]");

		// a command button
		$this->assertElementNotPresent("//input[@type='submit' and @value=\"Name: test, Param: value using callback\"]");
		$this->click("//input[@type='submit' and @value='click me']", "");
		$this->pause(800);
		$this->assertElementPresent("//input[@type='submit' and @value=\"Name: test, Param: value using callback\"]");

		// a button causing validation
		$this->assertNotVisible('ctl0_body_ctl2');
		$this->click("//input[@type='submit' and @value='submit']", "");
		$this->pause(800);
		$this->assertVisible('ctl0_body_ctl2');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->click("//input[@type='submit' and @value='submit']", "");
		$this->pause(800);
		$this->assertNotVisible('ctl0_body_ctl2');
		$this->assertElementPresent("//input[@type='submit' and @value=\"I'm clicked using callback\"]", "");
	}
}
