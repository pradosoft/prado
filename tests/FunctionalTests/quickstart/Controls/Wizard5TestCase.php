<?php

class Wizard5TestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TWizard.Sample5&amp;notheme=true", "");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// step 1
		$this->verifyTextPresent('Please let us know your preference');
		$this->verifyVisible('ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton');
		$this->verifyVisible('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton');
		$this->verifyAttribute('ctl0_body_Wizard1_SideBarList_ctl2_SideBarButton@disabled','regexp:true|disabled');
		$this->select('ctl0_body_Wizard1_DropDownList1', "label=Cyan");
		$this->clickAndWait('ctl0$body$Wizard1$ctl6$ctl0');

		// step 2
		$this->select('ctl0_body_Wizard1_Step2_DropDownList2','label=Football');
		$this->clickAndWait('ctl0$body$Wizard1$ctl8$ctl0');

		// step 1
		$this->verifySelected('ctl0_body_Wizard1_DropDownList1','label=Cyan');
		$this->clickAndWait('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton');

		// step 2
		$this->verifySelected('ctl0_body_Wizard1_Step2_DropDownList2','label=Football');
		$this->clickAndWait('ctl0$body$Wizard1$ctl8$ctl1');

		// step 3
		$this->verifyTextPresent('Your favorite color is: Cyan');
		$this->verifyTextPresent('Your favorite sport is: Football');
	}
}

?>