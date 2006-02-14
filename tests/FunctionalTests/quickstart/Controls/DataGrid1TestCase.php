<?php

class DataGrid1TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TDataGrid.Sample1&amp;notheme=true", "");

		// verify if all required texts are present
		$this->verifyTextPresent('id','');
		$this->verifyTextPresent('name','');
		$this->verifyTextPresent('quantity','');
		$this->verifyTextPresent('price','');
		$this->verifyTextPresent('imported','');
		$this->verifyTextPresent('ITN001','');
		$this->verifyTextPresent('Motherboard','');
		$this->verifyTextPresent('100','');
		$this->verifyTextPresent('true','');
		$this->verifyTextPresent('ITN019','');
		$this->verifyTextPresent('Speaker','');
		$this->verifyTextPresent('35','');
		$this->verifyTextPresent('65','');
		$this->verifyTextPresent('false','');

		// verify specific table tags
		$this->verifyElementPresent("ctl0_body_DataGrid");
		$this->verifyAttribute("ctl0_body_DataGrid@rules","all");
		$this->verifyAttribute("ctl0_body_DataGrid@cellpadding","2");
		$this->verifyAttribute("ctl0_body_DataGrid@cellspacing","0");
	}
}

?>