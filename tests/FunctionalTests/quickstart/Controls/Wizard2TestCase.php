<?php

class QuickstartWizard2TestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TWizard.Sample2&amp;notheme=true&amp;lang=en");

		$this->assertTitle("PRADO QuickStart Sample");

		// step 1
		$this->assertSourceContains('Please let us know your preference');
		$this->assertSourceNotContains('Thank you for your answer');
		$this->assertVisible('ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton');
		$this->assertAttribute('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton@disabled', 'regexp:true|disabled');
		$this->select('ctl0$body$Wizard1$DropDownList1', "Blue");
		$this->byName('ctl0$body$Wizard1$ctl6$ctl1')->click();

		// step 2
		$this->assertSourceContains('Your favorite color is: Blue');
		$this->assertSourceNotContains('Please let us know your preference');
		$this->assertSourceContains('Thank you for your answer');
	}
}
