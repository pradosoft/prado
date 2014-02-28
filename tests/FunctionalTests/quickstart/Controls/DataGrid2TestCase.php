<?php

class QuickstartDataGrid2TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TDataGrid.Sample2&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
		$this->assertTextPresent('Book Title','');
		$this->assertTextPresent('Publisher','');
		$this->assertTextPresent('Price','');
		$this->assertTextPresent('In-stock','');
		$this->assertTextPresent('Rating','');

		// verify book titles
		$this->assertElementPresent("//a[@href='http://www.amazon.com/gp/product/0596007124' and text()='Head First Design Patterns']",'');
		$this->assertElementPresent("//a[@href='http://www.amazon.com/gp/product/0321278658' and text()='Extreme Programming Explained : Embrace Change']",'');

		// verify publishers
		$this->assertTextPresent("O'Reilly Media, Inc.",'');
		$this->assertTextPresent("Addison-Wesley Professional",'');

		// verify prices
		$this->assertTextPresent("\$37.49",'');
		$this->assertTextPresent("\$38.49",'');

		// verify in-stock
		$this->verifyAttribute('ctl0_body_DataGrid_ctl1_ctl5@checked','regexp:true|checked');
		$this->verifyAttribute('ctl0_body_DataGrid_ctl1_ctl5@disabled','regexp:true|disabled');
		$this->verifyAttribute('ctl0_body_DataGrid_ctl2_ctl5@checked','regexp:true|checked');
		$this->verifyAttribute('ctl0_body_DataGrid_ctl6_ctl5@checked',null);
		$this->verifyAttribute('ctl0_body_DataGrid_ctl6_ctl5@disabled','regexp:true|disabled');

		// verify ratings
		//$this->assertElementPresent("//img[@src='images/star5.gif']",'');
		//$this->assertElementPresent("//img[@src='images/star2.gif']",'');

		// verify toggle column visibility
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl1\$c0' and @value='Book Title']", "");
		$this->assertTextNotPresent('Head First Design Patterns','');
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl1\$c3' and @value='In-stock']", "");
		$this->assertElementNotPresent('ctl0_body_DataGrid_ctl1_ctl5','');
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl1\$c3' and @value='In-stock']", "");
		$this->assertElementPresent('ctl0_body_DataGrid_ctl1_ctl5','');
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl1\$c0' and @value='Book Title']", "");
		$this->assertTextPresent('Head First Design Patterns','');
	}
}
