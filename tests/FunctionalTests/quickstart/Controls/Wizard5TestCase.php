<?php

class QuickstartWizard5TestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TWizard.Sample5&amp;notheme=true&amp;lang=en");

		$this->assertEquals("PRADO QuickStart Sample", $this->title());

		// step 1
		$this->assertSourceContains('Please let us know your preference');
		$this->assertVisible('ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton');
		$this->assertVisible('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton');
		$this->assertAttribute('ctl0_body_Wizard1_SideBarList_ctl2_SideBarButton@disabled', 'regexp:true|disabled');
		$this->select('ctl0_body_Wizard1_DropDownList1', "Cyan");
		$this->byName('ctl0$body$Wizard1$ctl4$ctl0')->click();
		$this->pause(50);

		// step 2
		$this->select('ctl0_body_Wizard1_Step2_DropDownList2', 'Football');
		$this->pause(50);
		$this->byName('ctl0$body$Wizard1$ctl6$ctl0')->click();
		$this->pause(50);

		// step 1
		$this->assertSelected('ctl0_body_Wizard1_DropDownList1', 'Cyan');
		$this->byId('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton')->click();
		$this->pause(50);

		// step 2
		$this->assertSelected('ctl0_body_Wizard1_Step2_DropDownList2', 'Football');
		$this->byName('ctl0$body$Wizard1$ctl6$ctl1')->click();
		$this->pause(50);

		// step 3
		$this->assertSourceContains('Your favorite color is: Cyan');
		$this->assertSourceContains('Your favorite sport is: Football');
	}
}
