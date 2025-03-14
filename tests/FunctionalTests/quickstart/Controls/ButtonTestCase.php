<?php

class QuickstartButtonTestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TButton.Home&amp;notheme=true&amp;lang=en");

		$this->assertTitle("PRADO QuickStart Sample");

		// a regular button
		$this->byXPath("//input[@type='submit' and @value='text']")->click();

		// a click button
		$this->assertElementNotPresent("//input[@type='submit' and @value=\"I'm clicked\"]");
		$this->byXPath("//input[@type='submit' and @value='click me']")->click();
		$this->pause(50);
		$this->assertElementPresent("//input[@type='submit' and @value=\"I'm clicked\"]");

		// a command button
		$this->assertElementNotPresent("//input[@type='submit' and @value=\"Name: test, Param: value\"]");
		$this->byXPath("//input[@type='submit' and @value='click me']")->click();
		$this->pause(50);
		$this->assertElementPresent("//input[@type='submit' and @value=\"Name: test, Param: value\"]");

		// a button causing validation
		$this->assertNotVisible('ctl0_body_ctl3');
		$this->byXPath("//input[@type='submit' and @value='submit']")->click();
		$this->assertVisible('ctl0_body_ctl3');
		$this->type("ctl0\$body\$TextBox", "test");
		$this->byXPath("//input[@type='submit' and @value='submit']")->click();
		$this->assertNotVisible('ctl0_body_ctl3');
	}
}
