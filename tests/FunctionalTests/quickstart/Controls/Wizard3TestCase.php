<?php

class QuickstartWizard3TestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TWizard.Sample3&amp;notheme=true&amp;lang=en");

		$this->assertEquals("PRADO QuickStart Sample", $this->title());

		// step 1
		$this->assertContains('A Mini Survey', $this->source());
		$this->assertContains('PRADO QuickStart Sample', $this->source());
		$this->byId('ctl0_body_Wizard3_StudentCheckBox')->click();
		$this->byName('ctl0$body$Wizard3$ctl4$ctl0')->click();

		// step 2
		$this->select('ctl0$body$Wizard3$DropDownList11', "Chemistry");
		$this->byName('ctl0$body$Wizard3$ctl5$ctl1')->click();

		// step 3
		$this->select('ctl0$body$Wizard3$DropDownList22', "Tennis");
		$this->byName('ctl0$body$Wizard3$ctl6$ctl1')->click();

		// step 4
		$this->assertContains('You are a college student', $this->source());
		$this->assertContains('You are in major: Chemistry', $this->source());
		$this->assertContains('Your favorite sport is: Tennis', $this->source());

		// run the example again. this time we skip the page asking about major
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TWizard.Sample3&amp;notheme=true");

		// step 1
		$this->byName('ctl0$body$Wizard3$ctl4$ctl0')->click();

		// step 3
		$this->select('ctl0$body$Wizard3$DropDownList22', "Baseball");
		$this->byName('ctl0$body$Wizard3$ctl6$ctl1')->click();

		// step 4
		$this->assertNotContains('You are a college student', $this->source());
		$this->assertContains('Your favorite sport is: Baseball', $this->source());
	}
}
