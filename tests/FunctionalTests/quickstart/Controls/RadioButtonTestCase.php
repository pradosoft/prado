<?php

class QuickstartRadioButtonTestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TRadioButton.Home&amp;notheme=true&amp;lang=en");

		$this->assertTitle("PRADO QuickStart Sample");

		// a regular radiobutton
		$this->byXPath("//input[@name='ctl0\$body\$ctl0' and @value='ctl0\$body\$ctl0']")->click();

		// a radiobutton with customized value
		$this->byXPath("//input[@name='ctl0\$body\$ctl1' and @value='value']")->click();

		// an auto postback radiobutton
		$this->assertSourceNotContains("I'm clicked");
		$this->byXPath("//input[@name='ctl0\$body\$ctl2' and @value='ctl0\$body\$ctl2']")->click();
		$this->assertSourceContains("I'm clicked");
		$this->byXPath("//input[@name='ctl0\$body\$ctl2' and @value='ctl0\$body\$ctl2']")->click();
		$this->assertSourceContains("I'm clicked");

		// a radiobutton causing validation on a textbox
		$this->assertNotVisible('ctl0_body_ctl3');
		$this->byXPath("//input[@name='ctl0\$body\$ctl4' and @value='ctl0\$body\$ctl4']")->click();
		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl3');
		$this->byXPath("//input[@name='ctl0\$body\$ctl4' and @value='ctl0\$body\$ctl4']")->click();
		$this->pause(500);
		$this->assertVisible('ctl0_body_ctl3');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->byXPath("//input[@name='ctl0\$body\$ctl4' and @value='ctl0\$body\$ctl4']")->click();
		$this->assertNotVisible('ctl0_body_ctl3');

		// a radiobutton validated by a required field validator
		$this->assertNotVisible('ctl0_body_ctl6');
		$this->byXPath("//input[@type='submit' and @value='Submit']")->click();
		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl6');
		$this->byXPath("//input[@name='ctl0\$body\$RadioButton' and @value='ctl0\$body\$RadioButton']")->click();
		$this->byXPath("//input[@type='submit' and @value='Submit']")->click();
		$this->assertNotVisible('ctl0_body_ctl6');

		// a radiobutton group
		$this->byName("ctl0\$body\$ctl7")->click();
		$this->assertSourceContains("Your selection is empty");
		$this->byXPath("//input[@name='ctl0\$body\$RadioGroup' and @value='ctl0\$body\$Radio2']")->click();
		$this->byName("ctl0\$body\$ctl7")->click();
		$this->assertSourceContains("Your selection is 2");
		$this->byXPath("//input[@name='ctl0\$body\$RadioGroup' and @value='ctl0\$body\$Radio3']")->click();
		$this->byXPath("//input[@name='ctl0\$body\$Radio4' and @value='ctl0\$body\$Radio4']")->click();
		$this->byName("ctl0\$body\$ctl7")->click();
		$this->assertSourceContains("Your selection is 34");
	}
}
