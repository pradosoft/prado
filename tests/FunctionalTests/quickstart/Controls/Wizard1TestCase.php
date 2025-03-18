<?php

class QuickstartWizard1TestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TWizard.Sample1&amp;notheme=true&amp;lang=en");

		$this->assertTitle("PRADO QuickStart Sample");

		// step 1
		$this->assertSourceContains('Wizard Step 1');
		$this->assertSourceNotContains('Wizard Step 2');
		$this->assertVisible('ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton');
		$this->assertAttribute('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton@disabled', 'regexp:true|disabled');
		$this->select('ctl0$body$Wizard1$DropDownList1', "Purple");
		$this->byName('ctl0$body$Wizard1$ctl6$ctl1')->click();

		// step 2
		$this->assertSourceContains('Your favorite color is: Purple');
		$this->assertSourceNotContains('Wizard Step 1');
		$this->assertSourceContains('Wizard Step 2');
	}
}
