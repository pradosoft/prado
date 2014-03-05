<?php

class QuickstartWizard4TestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TWizard.Sample4&amp;notheme=true&amp;lang=en");

		$this->assertEquals("PRADO QuickStart Sample", $this->title());

		// step 1
		$this->assertContains('Step 1 of 3', $this->source());
		$this->select('ctl0_body_Wizard1_DropDownList1', "Cyan");
		$this->byId('ctl0_body_Wizard1_SideBarList_ctl2_SideBarButton')->click();

		// step 3
		$this->assertContains('Step 3 of 3', $this->source());
		$this->assertContains('Thank you for completing this survey.', $this->source());
		$this->byId('ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton')->click();

		// step 1
		$this->assertSelected('ctl0_body_Wizard1_DropDownList1', "Cyan");
		$this->select('ctl0_body_Wizard1_DropDownList1', "Black");
		$this->byId('ctl0_body_Wizard1_ctl4_ctl0')->click();

		// step 2
		$this->assertContains('Step 2 of 3', $this->source());
		$this->assertContains('Your favorite color is: Black', $this->source());
		$this->byId('ctl0_body_Wizard1_ctl5_ctl0')->click();

		// step 1
		$this->assertContains('Step 1 of 3', $this->source());
		$this->assertSelected('ctl0_body_Wizard1_DropDownList1', "Black");
		$this->byId('ctl0_body_Wizard1_ctl4_ctl0')->click();

		// step 2
		$this->byId('ctl0_body_Wizard1_ctl5_ctl1')->click();

		// step 3
		$this->assertContains('Step 3 of 3', $this->source());
	}
}
