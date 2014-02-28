<?php

class QuickstartDataList2TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TDataList.Sample2&amp;notheme=true&amp;lang=en");

		// verify initial presentation
		$this->assertTextPresent("Motherboard", "");
		$this->assertTextPresent("Monitor", "");

		// verify selecting an item
		$this->clickAndWait("link=ITN003", "");
		$this->assertTextPresent("Quantity", "");
		$this->assertTextPresent("Price", "");
		$this->assertTextPresent("\$80", "");
		$this->clickAndWait("link=ITN005", "");
		$this->assertTextPresent("\$150", "");

		// verify editting an item
		$this->clickAndWait("id=ctl0_body_DataList_ctl5_ctl0", "");
		$this->type("ctl0\$body\$DataList\$ctl5\$ProductQuantity", "11");
		$this->type("ctl0\$body\$DataList\$ctl5\$ProductPrice", "140.99");
		$this->click("//input[@name='ctl0\$body\$DataList\$ctl5\$ProductImported']", "");
		$this->clickAndWait("link=Save", "");

		// verify item is saved
		$this->clickAndWait("link=ITN005", "");
		$this->assertTextPresent("\$140.99", "");
		$this->assertTextPresent("11", "");

		// verify editting another item
		$this->clickAndWait("id=ctl0_body_DataList_ctl3_ctl1", "");
		$this->type("ctl0\$body\$DataList\$ctl3\$ProductName", "Hard Drive");
		$this->type("ctl0\$body\$DataList\$ctl3\$ProductQuantity", "23");
		$this->click("//input[@name='ctl0\$body\$DataList\$ctl3\$ProductImported']", "");
		$this->clickAndWait("link=Cancel", "");

		// verify item is canceled
		$this->clickAndWait("link=ITN003", "");
		$this->assertTextPresent("2", "");
		$this->assertTextPresent("Harddrive", "");

		// verify item deletion
		$this->clickAndWait("id=ctl0_body_DataList_ctl3_ctl1", "");
		$this->verifyConfirmation("Are you sure?");
		$this->click("id=ctl0_body_DataList_ctl5_ctl2", "");
		$this->verifyConfirmationDismiss("Are you sure?");
		$this->assertTextPresent("Motherboard", "");
		$this->assertTextPresent("CPU", "");
		$this->assertTextNotPresent("Harddrive","");
		$this->assertTextPresent("Sound card", "");
		$this->assertTextPresent("Video card", "");
		$this->assertTextPresent("Keyboard","");
		$this->assertTextPresent("Monitor", "");
	}
}
