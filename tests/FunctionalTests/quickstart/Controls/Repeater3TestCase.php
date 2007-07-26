<?php

class Repeater3TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TRepeater.Sample3&amp;notheme=true&amp;lang=en", "");

		// verify product name is required
		$this->verifyNotVisible('ctl0_body_Repeater_ctl0_ctl0');
		$this->type("ctl0_body_Repeater_ctl0_ProductName", "");
		$this->click("//input[@type='submit' and @value='Save']", "");
		$this->verifyVisible('ctl0_body_Repeater_ctl0_ctl0');

		// verify product price is of proper format
		$this->verifyNotVisible('ctl0_body_Repeater_ctl0_ctl1');
		$this->type("ctl0_body_Repeater_ctl0_ProductPrice", "abc");
		$this->click("//input[@type='submit' and @value='Save']", "");
		$this->verifyVisible('ctl0_body_Repeater_ctl0_ctl1');

		// perform postback
		$this->click("ctl0_body_Repeater_ctl0_ProductImported",'');
		$this->type("ctl0_body_Repeater_ctl0_ProductName", "Mother Board");
		$this->type("ctl0_body_Repeater_ctl0_ProductPrice", "99.01");
		$this->select("ctl0_body_Repeater_ctl3_ProductCategory", "label=Accessories");
		$this->clickAndWait("//input[@type='submit' and @value='Save']", "");
		$this->verifyNotVisible('ctl0_body_Repeater_ctl0_ctl0');
		$this->verifyNotVisible('ctl0_body_Repeater_ctl0_ctl1');

		// verify postback results
		$this->verifyElementPresent("//td[text()='Mother Board']",'');
		$this->verifyElementNotPresent("//td[text()='Input Devices']",'');
		$this->verifyElementPresent("//td[text()='99.01']",'');
	}
}

?>