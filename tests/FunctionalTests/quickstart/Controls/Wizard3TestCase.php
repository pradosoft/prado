<?php

class QuickstartWizard3TestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TWizard.Sample3&amp;notheme=true&amp;lang=en");

		$this->assertTitle("PRADO QuickStart Sample");

		// step 1
		$this->assertSourceContains('A Mini Survey');
		$this->assertSourceContains('PRADO QuickStart Sample');
		$this->byId('ctl0_body_Wizard3_StudentCheckBox')->click();
		$this->pause(50);
		$this->byName('ctl0$body$Wizard3$ctl4$ctl0')->click();
		$this->pause(50);

		// step 2
		$this->select('ctl0$body$Wizard3$DropDownList11', "Chemistry");
		$this->pause(50);
		$this->byName('ctl0$body$Wizard3$ctl5$ctl1')->click();
		$this->pause(50);

		// step 3
		$this->select('ctl0$body$Wizard3$DropDownList22', "Tennis");
		$this->pause(50);
		$this->byName('ctl0$body$Wizard3$ctl6$ctl1')->click();

		// step 4
		$this->assertSourceContains('You are a college student');
		$this->assertSourceContains('You are in major: Chemistry');
		$this->assertSourceContains('Your favorite sport is: Tennis');

		// run the example again. this time we skip the page asking about major
		$this->url("quickstart/index.php?page=Controls.Samples.TWizard.Sample3&amp;notheme=true");

		// step 1
		$this->byName('ctl0$body$Wizard3$ctl4$ctl0')->click();
		$this->pause(50);

		// step 3
		$this->select('ctl0$body$Wizard3$DropDownList22', "Baseball");
		$this->pause(50);
		$this->byName('ctl0$body$Wizard3$ctl6$ctl1')->click();

		// step 4
		$this->assertSourceNotContains('You are a college student');
		$this->assertSourceContains('Your favorite sport is: Baseball');
	}
}
