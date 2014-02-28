<?php

class QuickstartDataGrid1TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TDataGrid.Sample1&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
		$this->assertTextPresent('id','');
		$this->assertTextPresent('name','');
		$this->assertTextPresent('quantity','');
		$this->assertTextPresent('price','');
		$this->assertTextPresent('imported','');
		$this->assertTextPresent('ITN001','');
		$this->assertTextPresent('Motherboard','');
		$this->assertTextPresent('100','');
		$this->assertTextPresent('true','');
		$this->assertTextPresent('ITN019','');
		$this->assertTextPresent('Speaker','');
		$this->assertTextPresent('35','');
		$this->assertTextPresent('65','');
		$this->assertTextPresent('false','');

		// verify specific table tags
		$this->assertElementPresent("ctl0_body_DataGrid");
		$this->verifyAttribute("ctl0_body_DataGrid@cellpadding","2");
	}
}
