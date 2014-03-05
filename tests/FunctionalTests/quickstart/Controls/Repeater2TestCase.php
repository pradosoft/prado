<?php

class QuickstartRepeater2TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TRepeater.Sample2&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
		$this->assertContains('North', $this->source());
		$this->assertContains('John', $this->source());
		$this->assertContains('Developer', $this->source());
		$this->assertContains('South', $this->source());
		$this->assertContains('Carter', $this->source());
		$this->assertContains('Program Manager', $this->source());

		// verify specific table tags
		$this->assertElementPresent("//table[@cellspacing='1']");
		$this->assertElementPresent("//td[@id='ctl0_body_Repeater_ctl1_Cell' and contains(text(),'North')]","");
		$this->assertElementPresent("//td[@id='ctl0_body_Repeater_ctl1_Cell']","");
		$this->assertElementPresent("//td[@id='ctl0_body_Repeater_ctl2_Cell']","");
		$this->assertElementPresent("//td[@id='ctl0_body_Repeater_ctl3_Cell']","");
		$this->assertElementPresent("//td[@id='ctl0_body_Repeater_ctl4_Cell']","");
		$this->assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl1_Repeater2_ctl1_Row']","");
		$this->assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl1_Repeater2_ctl2_Row']","");
		$this->assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl1_Repeater2_ctl3_Row']","");
		$this->assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl2_Repeater2_ctl1_Row']","");
		$this->assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl2_Repeater2_ctl2_Row']","");
		$this->assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl2_Repeater2_ctl3_Row']","");
		$this->assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl3_Repeater2_ctl1_Row']","");
		$this->assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl3_Repeater2_ctl2_Row']","");
		$this->assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl4_Repeater2_ctl1_Row']","");
		$this->assertElementPresent("//tr[@id='ctl0_body_Repeater_ctl4_Repeater2_ctl2_Row']","");
	}
}
