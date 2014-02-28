<?php

class QuickstartDataList1TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TDataList.Sample1&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
		$this->assertTextPresent('ITN001','');
		$this->assertTextPresent('$100','');
		$this->assertTextPresent('Motherboard','');
		$this->assertTextPresent('ITN018','');
		$this->assertTextPresent('Surge protector','');
		$this->assertTextPresent('45','');
		$this->assertTextPresent('$15','');
		$this->assertTextPresent('Total 19 products.','');
		$this->assertTextPresent('Computer Parts','');

		// verify specific table tags
		$this->assertElementPresent("ctl0_body_DataList");
		$this->assertElementPresent("//td[@align='right']");
	}
}
