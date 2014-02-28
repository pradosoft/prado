<?php

class QuickstartLinkButtonTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TLinkButton.Home&amp;notheme=true&amp;lang=en");

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
		$this->assertNotVisible('ctl0_body_ctl4');
		$this->click("link=submit", "");
//		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl4');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->clickAndWait("link=submit", "");
		$this->assertNotVisible('ctl0_body_ctl4');
	}
}
