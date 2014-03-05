<?php

class QuickstartRadioButtonTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TRadioButton.Home&amp;notheme=true&amp;lang=en");

		$this->assertEquals("PRADO QuickStart Sample", $this->title());

		// a regular radiobutton
		$this->byXPath("//input[@name='ctl0\$body\$ctl0' and @value='ctl0\$body\$ctl0']")->click();

		// a radiobutton with customized value
		$this->byXPath("//input[@name='ctl0\$body\$ctl1' and @value='value']")->click();

		// an auto postback radiobutton
		$this->assertNotContains("I'm clicked", $this->source());
		$this->byXPath("//input[@name='ctl0\$body\$ctl2' and @value='ctl0\$body\$ctl2']")->click();
		$this->assertContains("I'm clicked", $this->source());
		$this->byXPath("//input[@name='ctl0\$body\$ctl2' and @value='ctl0\$body\$ctl2']")->click();
		$this->assertContains("I'm clicked", $this->source());

		// a radiobutton causing validation on a textbox
		$this->assertNotVisible('ctl0_body_ctl3');
		$this->byXPath("//input[@name='ctl0\$body\$ctl4' and @value='ctl0\$body\$ctl4']")->click();
		$this->pause(1000);
		$this->assertVisible('ctl0_body_ctl3');
		$this->byXPath("//input[@name='ctl0\$body\$ctl4' and @value='ctl0\$body\$ctl4']")->click();
		$this->pause(1000);
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
		$this->assertContains("Your selection is empty", $this->source());
		$this->byXPath("//input[@name='ctl0\$body\$RadioGroup' and @value='ctl0\$body\$Radio2']")->click();
		$this->byName("ctl0\$body\$ctl7")->click();
		$this->assertContains("Your selection is 2", $this->source());
		$this->byXPath("//input[@name='ctl0\$body\$RadioGroup' and @value='ctl0\$body\$Radio3']")->click();
		$this->byXPath("//input[@name='ctl0\$body\$Radio4' and @value='ctl0\$body\$Radio4']")->click();
		$this->byName("ctl0\$body\$ctl7")->click();
		$this->assertContains("Your selection is 34", $this->source());
	}
}
