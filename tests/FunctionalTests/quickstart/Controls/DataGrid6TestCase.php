<?php

class QuickstartDataGrid6TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TDataGrid.Sample6&amp;notheme=true&amp;lang=en");

		// verify column headers
		$this->assertTextPresent('id','');
		$this->assertTextPresent('name','');
		$this->assertTextPresent('quantity','');
		$this->assertTextPresent('price','');
		$this->assertTextPresent('imported','');

		$this->assertTextPresent('ITN001','');
		$this->assertTextPresent('ITN002','');
		$this->assertTextPresent('ITN003','');
		$this->assertTextPresent('ITN004','');
		$this->assertTextPresent('ITN005','');
		$this->assertTextNotPresent('ITN006','');

		// verify paging
		$this->clickAndWait("link=2", "");
		$this->assertTextPresent('ITN006','');
		$this->assertTextPresent('ITN007','');
		$this->assertTextPresent('ITN008','');
		$this->assertTextPresent('ITN009','');
		$this->assertTextPresent('ITN010','');
		$this->assertTextNotPresent('ITN011','');
		$this->assertTextNotPresent('ITN005','');

		$this->clickAndWait("link=4", "");
		$this->assertTextPresent('ITN016','');
		$this->assertTextPresent('ITN017','');
		$this->assertTextPresent('ITN018','');
		$this->assertTextPresent('ITN019','');
		$this->assertTextNotPresent('ITN015','');

		$this->clickAndWait("link=1", "");
		$this->assertTextPresent('ITN001','');
		$this->assertTextPresent('ITN002','');
		$this->assertTextPresent('ITN003','');
		$this->assertTextPresent('ITN004','');
		$this->assertTextPresent('ITN005','');
		$this->assertTextNotPresent('ITN006','');
	}
}
