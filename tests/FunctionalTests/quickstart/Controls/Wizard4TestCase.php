<?php

class QuickstartWizard4TestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TWizard.Sample4&amp;notheme=true&amp;lang=en");

		$this->assertTitle("PRADO QuickStart Sample");
		$this->pause(100);

		// step 1
		$this->assertSourceContains('Step 1 of 3');
		$this->select('ctl0_body_Wizard1_DropDownList1', "Cyan");
		$this->byId('ctl0_body_Wizard1_SideBarList_ctl2_SideBarButton')->click();

		// step 3
		$this->assertSourceContains('Step 3 of 3');
		$this->assertSourceContains('Thank you for completing this survey.');
		$this->byId('ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton')->click();

		// step 1
		$this->pause(50);
		$this->assertSelected('ctl0_body_Wizard1_DropDownList1', "Cyan");
		$this->select('ctl0_body_Wizard1_DropDownList1', "Black");
		$this->byId('ctl0_body_Wizard1_ctl4_ctl0')->click();

		// step 2
		$this->assertSourceContains('Step 2 of 3');
		$this->assertSourceContains('Your favorite color is: Black');
		$this->byId('ctl0_body_Wizard1_ctl5_ctl0')->click();

		// step 1
		$this->assertSourceContains('Step 1 of 3');
		$this->assertSelected('ctl0_body_Wizard1_DropDownList1', "Black");
		$this->byId('ctl0_body_Wizard1_ctl4_ctl0')->click();

		// step 2
		$this->pause(50);
		$this->byId('ctl0_body_Wizard1_ctl5_ctl1')->click();

		// step 3
		$this->assertSourceContains('Step 3 of 3');
	}
}
