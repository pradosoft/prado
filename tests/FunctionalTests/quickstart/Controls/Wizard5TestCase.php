<?php

class QuickstartWizard5TestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TWizard.Sample5&amp;notheme=true&amp;lang=en");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// step 1
		$this->assertTextPresent('Please let us know your preference');
		$this->assertVisible('ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton');
		$this->assertVisible('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton');
		$this->verifyAttribute('ctl0_body_Wizard1_SideBarList_ctl2_SideBarButton@disabled','regexp:true|disabled');
		$this->select('ctl0_body_Wizard1_DropDownList1', "label=Cyan");
		$this->clickAndWait('ctl0$body$Wizard1$ctl4$ctl0');

		// step 2
		$this->select('ctl0_body_Wizard1_Step2_DropDownList2','label=Football');
		$this->clickAndWait('ctl0$body$Wizard1$ctl6$ctl0');

		// step 1
		$this->assertSelected('ctl0_body_Wizard1_DropDownList1','Cyan');
		$this->clickAndWait('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton');

		// step 2
		$this->assertSelected('ctl0_body_Wizard1_Step2_DropDownList2','Football');
		$this->clickAndWait('ctl0$body$Wizard1$ctl6$ctl1');

		// step 3
		$this->assertTextPresent('Your favorite color is: Cyan');
		$this->assertTextPresent('Your favorite sport is: Football');
	}
}
