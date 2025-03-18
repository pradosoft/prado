<?php

class QuickstartCheckBoxTestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TCheckBox.Home&amp;notheme=true&amp;lang=en");

		$this->assertTitle("PRADO QuickStart Sample");

		// a regular checkbox
		$this->byXPath("//input[@name='ctl0\$body\$ctl0']")->click();

		// a checkbox with customized value
		$this->byXPath("//input[@name='ctl0\$body\$ctl1' and @value='value']")->click();

		// an auto postback checkbox
		$this->assertSourceNotContains("I'm clicked");
		$this->byXPath("//input[@name='ctl0\$body\$ctl2']")->click();
		$this->assertSourceContains("I'm clicked");
		$this->byXPath("//input[@name='ctl0\$body\$ctl2']")->click();
		$this->assertSourceContains("I'm clicked");

		// a checkbox causing validation on a textbox
		$this->assertNotVisible('ctl0_body_ctl3');
		$this->byXPath("//input[@name='ctl0\$body\$ctl4']")->click();
//		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl3');
		$this->byXPath("//input[@name='ctl0\$body\$ctl4']")->click();
//		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl3');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->byXPath("//input[@name='ctl0\$body\$ctl4']")->click();
		$this->assertNotVisible('ctl0_body_ctl3');

		// a checkbox validated by a required field validator
		$this->assertNotVisible('ctl0_body_ctl6');
		$this->byXPath("//input[@type='submit' and @value='Submit']")->click();
//		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl6');
		$this->byXPath("//input[@name='ctl0\$body\$CheckBox']")->click();
		$this->byXPath("//input[@type='submit' and @value='Submit']")->click();
		$this->assertNotVisible('ctl0_body_ctl6');

		// a checkbox validated by a required field validator using AutoPostBack
		$this->assertNotVisible('ctl0_body_ctl7');
		$this->byXPath("//input[@name='ctl0\$body\$CheckBox2']")->click();
//		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl7');
//		$this->byXPath("//input[@name='ctl0\$body\$CheckBox2' and @value='ctl0\$body\$CheckBox2']")->click();
//		$this->assertNotVisible('ctl0_body_ctl7');
	}
}
