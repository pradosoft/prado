<?php

class DataGrid4TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TDataGrid.Sample4&amp;notheme=true&amp;lang=en", "");

		// verify the 2nd row of data
		$this->verifyTextPresent("Design Patterns: Elements of Reusable Object-Oriented Software", "");
		$this->verifyTextPresent("Addison-Wesley Professional", "");
		$this->verifyTextPresent("$47.04", "");
		$this->verifyAttribute('ctl0_body_DataGrid_ctl2_ctl5@checked','regexp:true|checked');
		$this->verifyAttribute('ctl0_body_DataGrid_ctl2_ctl5@disabled','regexp:true|disabled');

		// verify sorting
		$this->clickAndWait("link=Book Title", "");
		$this->verifyAttribute('ctl0_body_DataGrid_ctl1_ctl5@checked','regexp:false|null');
		$this->clickAndWait("link=Publisher", "");
		$this->verifyAttribute('ctl0_body_DataGrid_ctl6_ctl5@checked','regexp:false|null');
		$this->clickAndWait("link=Price", "");
		$this->verifyAttribute('ctl0_body_DataGrid_ctl2_ctl5@checked','regexp:false|null');
		$this->clickAndWait("link=In-stock", "");
		$this->verifyAttribute('ctl0_body_DataGrid_ctl1_ctl5@checked','regexp:false|null');
		$this->clickAndWait("link=Rating", "");
		$this->verifyAttribute('ctl0_body_DataGrid_ctl4_ctl5@checked','regexp:false|null');
	}
}

?>