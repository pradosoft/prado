<?php

class QuickstartWizard3TestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TWizard.Sample3&amp;notheme=true&amp;lang=en");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// step 1
		$this->assertTextPresent('A Mini Survey');
		$this->assertTextPresent('PRADO QuickStart Sample');
		$this->click('ctl0_body_Wizard3_StudentCheckBox');
		$this->clickAndWait('ctl0$body$Wizard3$ctl4$ctl0');

		// step 2
		$this->select('ctl0$body$Wizard3$DropDownList11', "label=Chemistry");
		$this->clickAndWait('ctl0$body$Wizard3$ctl5$ctl1');

		// step 3
		$this->select('ctl0$body$Wizard3$DropDownList22', "label=Tennis");
		$this->clickAndWait('ctl0$body$Wizard3$ctl6$ctl1');

		// step 4
		$this->assertTextPresent('You are a college student');
		$this->assertTextPresent('You are in major: Chemistry');
		$this->assertTextPresent('Your favorite sport is: Tennis');

		// run the example again. this time we skip the page asking about major
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TWizard.Sample3&amp;notheme=true");

		// step 1
		$this->clickAndWait('ctl0$body$Wizard3$ctl4$ctl0');

		// step 3
		$this->select('ctl0$body$Wizard3$DropDownList22', "label=Baseball");
		$this->clickAndWait('ctl0$body$Wizard3$ctl6$ctl1');

		// step 4
		$this->assertTextNotPresent('You are a college student');
		$this->assertTextPresent('Your favorite sport is: Baseball');
	}
}
