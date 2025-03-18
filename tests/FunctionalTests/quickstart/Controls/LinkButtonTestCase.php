<?php

class QuickstartLinkButtonTestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TLinkButton.Home&amp;notheme=true&amp;lang=en");

		$this->assertTitle("PRADO QuickStart Sample");

		// regular buttons
		$this->byLinkText("link button")->click();
		$this->pause(50);
		$this->byXPath("//a[contains(text(),'body content')]")->click();
		$this->pause(50);

		// a click button
		$this->byLinkText("click me")->click();
		$this->pause(50);
		$this->byLinkText("I'm clicked")->click();
		$this->pause(50);

		// a command button
		$this->byLinkText("click me")->click();
		$this->pause(50);
		$this->byXPath("//a[contains(text(),'Name: test, Param: value')]")->click();

		// a button causing validation
		$this->assertNotVisible('ctl0_body_ctl4');
		$this->byLinkText("submit")->click();
//		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl4');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->byLinkText("submit")->click();
		$this->assertNotVisible('ctl0_body_ctl4');
	}
}
