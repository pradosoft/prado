<?php

//$Id: ActiveCheckBoxTestCase.php 3187 2012-07-12 11:21:01Z ctrlaltca $
class QuickstartActiveCheckBoxTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=ActiveControls.Samples.TActiveCheckBox.Home&amp;notheme=true&amp;lang=en");

		$this->assertEquals("PRADO QuickStart Sample", $this->title());

		$this->assertSourceContains('TActiveCheckBox Samples (AJAX)');


		// an auto postback checkbox
		$this->assertSourceNotContains("ctl0_body_ctl0 clicked using callback");
		$this->byXPath("//input[@name='ctl0\$body\$ctl0']")->click();
		$this->pause(800);
		$this->assertTrue($this->byXPath("//input[@name='ctl0\$body\$ctl0']")->selected());
		$this->assertSourceContains("ctl0_body_ctl0 clicked using callback");
		$this->byXPath("//input[@name='ctl0\$body\$ctl0']")->click();
		$this->pause(800);
		$this->assertSourceContains("ctl0_body_ctl0 clicked using callback");
		$this->assertFalse($this->byXPath("//input[@name='ctl0\$body\$ctl0']")->selected());

		// a checkbox causing validation on a textbox
		$this->assertNotVisible('ctl0_body_ctl1');
		$this->byXPath("//input[@name='ctl0\$body\$ctl2']")->click();
		$this->assertVisible('ctl0_body_ctl1');
		$this->byXPath("//input[@name='ctl0\$body\$ctl2']")->click();
		$this->assertVisible('ctl0_body_ctl3');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->byXPath("//input[@name='ctl0\$body\$ctl2']")->click();
		$this->pause(800);
		$this->assertNotVisible('ctl0_body_ctl1');
		$this->assertSourceContains("ctl0_body_ctl2 clicked using callback");

		// a checkbox validated by a required field validator
		$this->assertFalse($this->byXPath("//input[@name='ctl0\$body\$CheckBox']")->selected());
		$this->assertNotVisible('ctl0_body_ctl4');
		$this->byXPath("//input[@type='submit' and @value='Submit']")->click();
		$this->assertVisible('ctl0_body_ctl4');
		$this->byXPath("//input[@name='ctl0\$body\$CheckBox']")->click();
		$this->assertTrue($this->byXPath("//input[@name='ctl0\$body\$CheckBox']")->selected());
		$this->byXPath("//input[@type='submit' and @value='Submit']")->click();
		$this->pause(800);
		$this->assertNotVisible('ctl0_body_ctl4');
		$this->assertSourceContains("ctl0_body_CheckBox clicked");

		// a checkbox validated by a required field validator using AutoPostBack
		$this->assertTrue($this->byXPath("//input[@name='ctl0\$body\$CheckBox2']")->selected());
		$this->assertNotVisible('ctl0_body_ctl5');
		$this->byXPath("//input[@name='ctl0\$body\$CheckBox2']")->click();
		$this->assertVisible('ctl0_body_ctl5');
		$this->assertTrue($this->byXPath("//input[@name='ctl0\$body\$CheckBox2']")->selected());
	}
}
