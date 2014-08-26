<?php

class QuickstartWizard1TestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TWizard.Sample1&amp;notheme=true&amp;lang=en");

		$this->assertEquals("PRADO QuickStart Sample", $this->title());

		// step 1
		$this->assertContains('Wizard Step 1', $this->source());
		$this->assertNotContains('Wizard Step 2', $this->source());
		$this->assertVisible('ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton');
		$this->assertAttribute('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton@disabled','regexp:true|disabled');
		$this->select('ctl0$body$Wizard1$DropDownList1', "Purple");
		$this->byName('ctl0$body$Wizard1$ctl6$ctl1')->click();

		// step 2
		$this->assertContains('Your favorite color is: Purple', $this->source());
		$this->assertNotContains('Wizard Step 1', $this->source());
		$this->assertContains('Wizard Step 2', $this->source());
	}
}
