<?php

class QuickstartImageButtonTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TImageButton.Home&amp;notheme=true&amp;lang=en");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// a click button
		$this->clickAndWait("//input[@type='image' and @alt='hello world']", "");
		$this->assertTextPresent("You clicked at ","");

		// a command button
		$this->clickAndWait("ctl0\$body\$ctl1", "");
		$this->assertTextPresent("Command name: test, Command parameter: value","");

		// a button causing validation
		$this->assertNotVisible('ctl0_body_ctl2');
		$this->click("id=ctl0_body_ctl3", "");
//		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl2');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->clickAndWait("id=ctl0_body_ctl3", "");
		$this->assertNotVisible('ctl0_body_ctl2');
	}
}
