<?php

class DataGrid6TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TDataGrid.Sample6&amp;notheme=true", "");

		// verify column headers
		$this->verifyTextPresent('id','');
		$this->verifyTextPresent('name','');
		$this->verifyTextPresent('quantity','');
		$this->verifyTextPresent('price','');
		$this->verifyTextPresent('imported','');

		$this->verifyTextPresent('ITN001','');
		$this->verifyTextPresent('ITN002','');
		$this->verifyTextPresent('ITN003','');
		$this->verifyTextPresent('ITN004','');
		$this->verifyTextPresent('ITN005','');
		$this->verifyTextNotPresent('ITN006','');

		// verify paging
		$this->clickAndWait("link=2", "");
		$this->verifyTextPresent('ITN006','');
		$this->verifyTextPresent('ITN007','');
		$this->verifyTextPresent('ITN008','');
		$this->verifyTextPresent('ITN009','');
		$this->verifyTextPresent('ITN010','');
		$this->verifyTextNotPresent('ITN011','');
		$this->verifyTextNotPresent('ITN005','');

		$this->clickAndWait("link=4", "");
		$this->verifyTextPresent('ITN016','');
		$this->verifyTextPresent('ITN017','');
		$this->verifyTextPresent('ITN018','');
		$this->verifyTextPresent('ITN019','');
		$this->verifyTextNotPresent('ITN015','');

		$this->clickAndWait("link=1", "");
		$this->verifyTextPresent('ITN001','');
		$this->verifyTextPresent('ITN002','');
		$this->verifyTextPresent('ITN003','');
		$this->verifyTextPresent('ITN004','');
		$this->verifyTextPresent('ITN005','');
		$this->verifyTextNotPresent('ITN006','');
	}
}

?>