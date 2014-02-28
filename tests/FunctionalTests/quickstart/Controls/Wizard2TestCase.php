<?php

class QuickstartWizard2TestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TWizard.Sample2&amp;notheme=true&amp;lang=en");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// step 1
		$this->assertTextPresent('Please let us know your preference');
		$this->assertTextNotPresent('Thank you for your answer');
		$this->assertVisible('ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton');
		$this->verifyAttribute('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton@disabled','regexp:true|disabled');
		$this->select('ctl0$body$Wizard1$DropDownList1', "label=Blue");
		$this->clickAndWait('ctl0$body$Wizard1$ctl6$ctl1');

		// step 2
		$this->assertTextPresent('Your favorite color is: Blue');
		$this->assertTextNotPresent('Please let us know your preference');
		$this->assertTextPresent('Thank you for your answer');
	}
}
