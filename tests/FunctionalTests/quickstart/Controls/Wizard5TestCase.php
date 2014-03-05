<?php

class QuickstartWizard5TestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TWizard.Sample5&amp;notheme=true&amp;lang=en");

		$this->assertEquals("PRADO QuickStart Sample", $this->title());

		// step 1
		$this->assertContains('Please let us know your preference', $this->source());
		$this->assertVisible('ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton');
		$this->assertVisible('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton');
		$this->assertAttribute('ctl0_body_Wizard1_SideBarList_ctl2_SideBarButton@disabled','regexp:true|disabled');
		$this->select('ctl0_body_Wizard1_DropDownList1', "Cyan");
		$this->byName('ctl0$body$Wizard1$ctl4$ctl0')->click();

		// step 2
		$this->select('ctl0_body_Wizard1_Step2_DropDownList2','Football');
		$this->byName('ctl0$body$Wizard1$ctl6$ctl0')->click();

		// step 1
		$this->assertSelected('ctl0_body_Wizard1_DropDownList1','Cyan');
		$this->byId('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton')->click();

		// step 2
		$this->assertSelected('ctl0_body_Wizard1_Step2_DropDownList2','Football');
		$this->byName('ctl0$body$Wizard1$ctl6$ctl1')->click();

		// step 3
		$this->assertContains('Your favorite color is: Cyan', $this->source());
		$this->assertContains('Your favorite sport is: Football', $this->source());
	}
}
