<?php
//$Id$
class ActiveButtonTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=ActiveControls.Samples.TActiveButton.Home&amp;notheme=true");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		$this->assertTextPresent('TActiveButton Samples (AJAX)');

		// a click button
		$this->verifyElementNotPresent("//input[@type='submit' and @value=\"I'm clicked\"]");
		$this->click("//input[@type='submit' and @value='click me']", "");
		$this->pause(800);
		$this->verifyElementPresent("//input[@type='submit' and @value=\"I'm clicked\"]");

		// a command button
		$this->verifyElementNotPresent("//input[@type='submit' and @value=\"Name: test, Param: value using callback\"]");
		$this->click("//input[@type='submit' and @value='click me']", "");
		$this->pause(800);
		$this->verifyElementPresent("//input[@type='submit' and @value=\"Name: test, Param: value using callback\"]");

		// a button causing validation
		$this->verifyNotVisible('ctl0_body_ctl2');
		$this->click("//input[@type='submit' and @value='submit']", "");
		$this->pause(800);
		$this->verifyVisible('ctl0_body_ctl2');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->click("//input[@type='submit' and @value='submit']", "");
		$this->pause(800);
		$this->verifyNotVisible('ctl0_body_ctl2');
		$this->verifyElementPresent("//input[@type='submit' and @value=\"I'm clicked using callback\"]", "");
	}
}

?>