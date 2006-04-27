<?php

class Wizard2TestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TWizard.Sample2&amp;notheme=true", "");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// step 1
		$this->verifyTextPresent('Please let us know your preference');
		$this->verifyTextNotPresent('Thank you for your answer');
		$this->verifyVisible('ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton');
		$this->verifyAttribute('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton@disabled','regexp:true|disabled');
		$this->select('ctl0$body$Wizard1$DropDownList1', "label=Blue");
		$this->clickAndWait('ctl0$body$Wizard1$ctl8$ctl1');

		// step 2
		$this->verifyTextPresent('Your favorite color is: Blue');
		$this->verifyTextNotPresent('Please let us know your preference');
		$this->verifyTextPresent('Thank you for your answer');
	}
}

?>