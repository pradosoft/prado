<?php

class QuickstartWizard4TestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TWizard.Sample4&amp;notheme=true&amp;lang=en");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// step 1
		$this->assertTextPresent('Step 1 of 3');
		$this->select('ctl0_body_Wizard1_DropDownList1', "label=Cyan");
		$this->clickAndWait('ctl0_body_Wizard1_SideBarList_ctl2_SideBarButton');

		// step 3
		$this->assertTextPresent('Step 3 of 3');
		$this->assertTextPresent('Thank you for completing this survey.');
		$this->clickAndWait('ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton');

		// step 1
		$this->assertSelected('ctl0_body_Wizard1_DropDownList1', "Cyan");
		$this->select('ctl0_body_Wizard1_DropDownList1', "label=Black");
		$this->clickAndWait('ctl0_body_Wizard1_ctl4_ctl0');

		// step 2
		$this->assertTextPresent('Step 2 of 3');
		$this->assertTextPresent('Your favorite color is: Black');
		$this->clickAndWait('ctl0_body_Wizard1_ctl5_ctl0');

		// step 1
		$this->assertTextPresent('Step 1 of 3');
		$this->assertSelected('ctl0_body_Wizard1_DropDownList1', "Black");
		$this->clickAndWait('ctl0_body_Wizard1_ctl4_ctl0');

		// step 2
		$this->clickAndWait('ctl0_body_Wizard1_ctl5_ctl1');

		// step 3
		$this->assertTextPresent('Step 3 of 3');
	}
}
