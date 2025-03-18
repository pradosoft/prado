<?php

class QuickstartImageButtonTestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TImageButton.Home&amp;notheme=true&amp;lang=en");

		$this->assertTitle("PRADO QuickStart Sample");

		// a click button
		$this->byXPath("//input[@type='image' and @alt='hello world']")->click();
		$this->assertSourceContains("You clicked at ");

		// a command button
		$this->byName("ctl0\$body\$ctl1")->click();
		$this->assertSourceContains("Command name: test, Command parameter: value");

		// a button causing validation
		$this->assertNotVisible('ctl0_body_ctl2');
		$this->byId("ctl0_body_ctl3")->click();
//		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl2');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->byId("ctl0_body_ctl3")->click();
		$this->assertNotVisible('ctl0_body_ctl2');
	}
}
