<?php

class ImageButtonTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TImageButton.Home&functionaltest=true", "");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// a click button
		$this->clickAndWait("//input[@type='image' and @alt='hello world']", "");
		$this->verifyTextPresent("You clicked at ","");

		// a command button
		$this->clickAndWait("ctl0\$body\$ctl1", "");
		$this->verifyTextPresent("Command name: test, Command parameter: value","");

		// a button causing validation
		$this->verifyNotVisible('ctl0_body_ctl2');
		$this->click("id=ctl0_body_ctl3", "");
		$this->pause(1000);
		$this->verifyVisible('ctl0_body_ctl2');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->clickAndWait("id=ctl0_body_ctl3", "");
		$this->verifyNotVisible('ctl0_body_ctl2');
	}
}

?>