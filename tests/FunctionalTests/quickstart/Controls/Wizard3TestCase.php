<?php

class Wizard3TestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TWizard.Sample3&amp;notheme=true", "");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// step 1
		$this->verifyTextPresent('A Mini Survey');
		$this->verifyTextPresent('PRADO QuickStart Sample');
		$this->click('ctl0_body_Wizard3_StudentCheckBox');
		$this->clickAndWait('ctl0$body$Wizard3$ctl8$ctl0');

		// step 2
		$this->select('ctl0$body$Wizard3$DropDownList11', "label=Chemistry");
		$this->clickAndWait('ctl0$body$Wizard3$ctl9$ctl1');

		// step 3
		$this->select('ctl0$body$Wizard3$DropDownList22', "label=Tennis");
		$this->clickAndWait('ctl0$body$Wizard3$ctl10$ctl1');

		// step 4
		$this->verifyTextPresent('You are a college student');
		$this->verifyTextPresent('You are in major: Chemistry');
		$this->verifyTextPresent('Your favorite sport is: Tennis');

		// run the example again. this time we skip the page asking about major
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TWizard.Sample3&amp;notheme=true", "");

		// step 1
		$this->clickAndWait('ctl0$body$Wizard3$ctl8$ctl0');

		// step 3
		$this->select('ctl0$body$Wizard3$DropDownList22', "label=Baseball");
		$this->clickAndWait('ctl0$body$Wizard3$ctl10$ctl1');

		// step 4
		$this->verifyTextNotPresent('You are a college student');
		$this->verifyTextPresent('Your favorite sport is: Baseball');
	}
}

?>