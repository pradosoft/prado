<?php

class DataGrid2TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TDataGrid.Sample2&amp;notheme=true", "");

		// verify if all required texts are present
		$this->verifyTextPresent('Book Title','');
		$this->verifyTextPresent('Publisher','');
		$this->verifyTextPresent('Price','');
		$this->verifyTextPresent('In-stock','');
		$this->verifyTextPresent('Rating','');

		// verify book titles
		$this->verifyElementPresent("//a[@href='http://www.amazon.com/gp/product/0596007124' and text()='Head First Design Patterns']",'');
		$this->verifyElementPresent("//a[@href='http://www.amazon.com/gp/product/0321278658' and text()='Extreme Programming Explained : Embrace Change']",'');

		// verify publishers
		$this->verifyTextPresent("O'Reilly Media, Inc.",'');
		$this->verifyTextPresent("Addison-Wesley Professional",'');

		// verify prices
		$this->verifyTextPresent("\$37.49",'');
		$this->verifyTextPresent("\$38.49",'');

		// verify in-stock
		$this->verifyAttribute('ctl0_body_DataGrid_ctl1_ctl6@checked','regexp:true|checked');
		$this->verifyAttribute('ctl0_body_DataGrid_ctl1_ctl6@disabled','regexp:true|disabled');
		$this->verifyAttribute('ctl0_body_DataGrid_ctl2_ctl6@checked','regexp:true|checked');
		$this->verifyAttribute('ctl0_body_DataGrid_ctl6_ctl6@checked','regexp:false|null');
		$this->verifyAttribute('ctl0_body_DataGrid_ctl6_ctl6@disabled','regexp:true|disabled');

		// verify ratings
		//$this->verifyElementPresent("//img[@src='images/star5.gif']",'');
		//$this->verifyElementPresent("//img[@src='images/star2.gif']",'');

		// verify toggle column visibility
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl1\$c0' and @value='Book Title']", "");
		$this->verifyTextNotPresent('Head First Design Patterns','');
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl1\$c3' and @value='In-stock']", "");
		$this->verifyElementNotPresent('ctl0_body_DataGrid_ctl1_ctl6','');
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl1\$c3' and @value='In-stock']", "");
		$this->verifyElementPresent('ctl0_body_DataGrid_ctl1_ctl6','');
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl1\$c0' and @value='Book Title']", "");
		$this->verifyTextPresent('Head First Design Patterns','');
	}
}

?>