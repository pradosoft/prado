<?php

class Repeater2TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TRepeater.Sample2&amp;notheme=true", "");

		// verify if all required texts are present
		$this->verifyTextPresent('North','');
		$this->verifyTextPresent('John','');
		$this->verifyTextPresent('Developer','');
		$this->verifyTextPresent('South','');
		$this->verifyTextPresent('Carter','');
		$this->verifyTextPresent('Program Manager','');

		// verify specific table tags
		$this->verifyElementPresent("//table[@cellspacing='1']");
		$this->verifyElementPresent("//td[@id='ctl0_body_Repeater_ctl1_Cell' and contains(text(),'North')]","");
		$this->verifyElementPresent("//td[@id='ctl0_body_Repeater_ctl1_Cell']","");
		$this->verifyElementPresent("//td[@id='ctl0_body_Repeater_ctl2_Cell']","");
		$this->verifyElementPresent("//td[@id='ctl0_body_Repeater_ctl3_Cell']","");
		$this->verifyElementPresent("//td[@id='ctl0_body_Repeater_ctl4_Cell']","");
		$this->verifyElementPresent("//tr[@id='ctl0_body_Repeater_ctl1_Repeater2_ctl1_Row']","");
		$this->verifyElementPresent("//tr[@id='ctl0_body_Repeater_ctl1_Repeater2_ctl2_Row']","");
		$this->verifyElementPresent("//tr[@id='ctl0_body_Repeater_ctl1_Repeater2_ctl3_Row']","");
		$this->verifyElementPresent("//tr[@id='ctl0_body_Repeater_ctl2_Repeater2_ctl1_Row']","");
		$this->verifyElementPresent("//tr[@id='ctl0_body_Repeater_ctl2_Repeater2_ctl2_Row']","");
		$this->verifyElementPresent("//tr[@id='ctl0_body_Repeater_ctl2_Repeater2_ctl3_Row']","");
		$this->verifyElementPresent("//tr[@id='ctl0_body_Repeater_ctl3_Repeater2_ctl1_Row']","");
		$this->verifyElementPresent("//tr[@id='ctl0_body_Repeater_ctl3_Repeater2_ctl2_Row']","");
		$this->verifyElementPresent("//tr[@id='ctl0_body_Repeater_ctl4_Repeater2_ctl1_Row']","");
		$this->verifyElementPresent("//tr[@id='ctl0_body_Repeater_ctl4_Repeater2_ctl2_Row']","");
	}
}

?>