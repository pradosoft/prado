<?php

class LinkButtonTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TLinkButton.Home", "");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// regular buttons
		$this->clickAndWait("link=link button", "");
		$this->clickAndWait("//a[contains(text(),'body content')]", "");

		// a click button
		$this->clickAndWait("link=click me", "");
		$this->clickAndWait("link=I'm clicked", "");

		// a command button
		$this->clickAndWait("link=click me", "");
		$this->clickAndWait("//a[contains(text(),'Name: test, Param: value')]", "");

		// a button causing validation
		$this->verifyNotVisible('ctl0_body_ctl4');
		$this->click("link=submit", "");
		$this->pause(1000);
		$this->verifyVisible('ctl0_body_ctl4');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->clickAndWait("link=submit", "");
		$this->verifyNotVisible('ctl0_body_ctl4');
	}
}

?>