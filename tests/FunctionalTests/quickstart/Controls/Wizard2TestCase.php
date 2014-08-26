<?php

class QuickstartWizard2TestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TWizard.Sample2&amp;notheme=true&amp;lang=en");

		$this->assertEquals("PRADO QuickStart Sample", $this->title());

		// step 1
		$this->assertContains('Please let us know your preference', $this->source());
		$this->assertNotContains('Thank you for your answer', $this->source());
		$this->assertVisible('ctl0_body_Wizard1_SideBarList_ctl0_SideBarButton');
		$this->assertAttribute('ctl0_body_Wizard1_SideBarList_ctl1_SideBarButton@disabled','regexp:true|disabled');
		$this->select('ctl0$body$Wizard1$DropDownList1', "Blue");
		$this->byName('ctl0$body$Wizard1$ctl6$ctl1')->click();

		// step 2
		$this->assertContains('Your favorite color is: Blue', $this->source());
		$this->assertNotContains('Please let us know your preference', $this->source());
		$this->assertContains('Thank you for your answer', $this->source());
	}
}
