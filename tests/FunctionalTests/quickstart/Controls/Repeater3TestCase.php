<?php

class QuickstartRepeater3TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TRepeater.Sample3&amp;notheme=true&amp;lang=en");

		// verify product name is required
		$this->assertNotVisible('ctl0_body_Repeater_ctl0_ctl0');
		$this->type("ctl0_body_Repeater_ctl0_ProductName", "");
		$this->click("//input[@type='submit' and @value='Save']", "");
		$this->assertVisible('ctl0_body_Repeater_ctl0_ctl0');

		// verify product price is of proper format
		$this->assertNotVisible('ctl0_body_Repeater_ctl0_ctl1');
		$this->type("ctl0_body_Repeater_ctl0_ProductPrice", "abc");
		$this->click("//input[@type='submit' and @value='Save']", "");
		$this->assertVisible('ctl0_body_Repeater_ctl0_ctl1');

		// perform postback
		$this->click("ctl0_body_Repeater_ctl0_ProductImported",'');
		$this->type("ctl0_body_Repeater_ctl0_ProductName", "Mother Board");
		$this->type("ctl0_body_Repeater_ctl0_ProductPrice", "99.01");
		$this->select("ctl0_body_Repeater_ctl3_ProductCategory", "label=Accessories");
		$this->clickAndWait("//input[@type='submit' and @value='Save']", "");
		$this->assertNotVisible('ctl0_body_Repeater_ctl0_ctl0');
		$this->assertNotVisible('ctl0_body_Repeater_ctl0_ctl1');

		// verify postback results
		$this->assertElementPresent("//td[text()='Mother Board']",'');
		$this->assertElementNotPresent("//td[text()='Input Devices']",'');
		$this->assertElementPresent("//td[text()='99.01']",'');
	}
}
