<?php

class DataList2TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TDataList.Sample2&amp;notheme=true", "");

		// verify initial presentation
		$this->verifyTextPresent("Motherboard ", "");
		$this->verifyTextPresent("Monitor ", "");

		// verify selecting an item
		$this->clickAndWait("link=ITN003", "");
		$this->verifyTextPresent("Quantity", "");
		$this->verifyTextPresent("Price", "");
		$this->verifyTextPresent("\$80", "");
		$this->clickAndWait("link=ITN005", "");
		$this->verifyTextPresent("\$150", "");

		// verify editting an item
		$this->clickAndWait("id=ctl0_body_DataList_ctl5_ctl4", "");
		$this->type("ctl0\$body\$DataList\$ctl5\$ProductQuantity", "11");
		$this->type("ctl0\$body\$DataList\$ctl5\$ProductPrice", "140.99");
		$this->click("//input[@name='ctl0\$body\$DataList\$ctl5\$ProductImported' and @value='ctl0\$body\$DataList\$ctl5\$ProductImported']", "");
		$this->clickAndWait("link=Save", "");

		// verify item is saved
		$this->clickAndWait("link=ITN005", "");
		$this->verifyTextPresent("\$140.99", "");
		$this->verifyTextPresent("11", "");

		// verify editting another item
		$this->clickAndWait("id=ctl0_body_DataList_ctl3_ctl2", "");
		$this->type("ctl0\$body\$DataList\$ctl3\$ProductName", "Hard Drive");
		$this->type("ctl0\$body\$DataList\$ctl3\$ProductQuantity", "23");
		$this->click("//input[@name='ctl0\$body\$DataList\$ctl3\$ProductImported' and @value='ctl0\$body\$DataList\$ctl3\$ProductImported']", "");
		$this->clickAndWait("link=Cancel", "");

		// verify item is canceled
		$this->clickAndWait("link=ITN003", "");
		$this->verifyTextPresent("2", "");
		$this->verifyTextPresent("Harddrive 	", "");

		// verify item deletion
		$this->clickAndWait("id=ctl0_body_DataList_ctl3_ctl5", "");
		$this->verifyConfirmation("Are you sure?");
		$this->chooseCancelOnNextConfirmation();
		$this->click("id=ctl0_body_DataList_ctl5_ctl3", "");
		$this->verifyTextPresent("Motherboard ", "");
		$this->verifyTextPresent("CPU ", "");
		$this->verifyTextNotPresent("Harddrive","");
		$this->verifyTextPresent("Sound card", "");
		$this->verifyTextPresent("Video card", "");
		$this->verifyTextPresent("Keyboard","");
		$this->verifyTextPresent("Monitor ", "");
	}
}

?>