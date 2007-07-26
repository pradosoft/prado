<?php

class DataList1TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TDataList.Sample1&amp;notheme=true&amp;lang=en", "");

		// verify if all required texts are present
		$this->verifyTextPresent('ITN001','');
		$this->verifyTextPresent('$100','');
		$this->verifyTextPresent('Motherboard','');
		$this->verifyTextPresent('ITN018','');
		$this->verifyTextPresent('Surge protector','');
		$this->verifyTextPresent('45','');
		$this->verifyTextPresent('$15','');
		$this->verifyTextPresent('Total 19 products.','');
		$this->verifyTextPresent('Computer Parts','');

		// verify specific table tags
		$this->verifyElementPresent("ctl0_body_DataList");
		$this->verifyElementPresent("//td[@align='right']");
	}
}

?>