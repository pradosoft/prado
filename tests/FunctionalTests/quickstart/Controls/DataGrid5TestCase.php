<?php

class DataGrid5TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TDataGrid.Sample5&amp;notheme=true&amp;lang=en", "");

		// verify if all required texts are present
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

		// show top pager
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl1\$c0' and @value='Top']", "");
		$this->clickAndWait("id=ctl0_body_DataGrid_ctl8_ctl3", "");
		$this->clickAndWait("link=1", "");
		// hide top pager
		$this->clickAndWait("//input[@name='ctl0\$body\$ctl1\$c0' and @value='Top']", "");

		// change next prev caption
		$this->type("ctl0\$body\$NextPageText", "Next Page");
		$this->type("ctl0\$body\$PrevPageText", "Prev Page");
		$this->clickAndWait("//input[@type='submit' and @value='Submit']", "");

		// verify next prev paging
		$this->verifyTextPresent('ITN001','');
		$this->verifyTextNotPresent('ITN006','');
		$this->clickAndWait("link=Next Page", "");
		$this->verifyTextNotPresent('ITN005','');
		$this->verifyTextPresent('ITN006','');
		$this->verifyTextNotPresent('ITN011','');
		$this->clickAndWait("link=Next Page", "");
		$this->verifyTextNotPresent('ITN010','');
		$this->verifyTextPresent('ITN011','');
		$this->verifyTextNotPresent('ITN016','');
		$this->clickAndWait("link=Next Page", "");
		$this->verifyTextNotPresent('ITN015','');
		$this->verifyTextPresent('ITN016','');
		$this->clickAndWait("link=Prev Page", "");
		$this->verifyTextNotPresent('ITN010','');
		$this->verifyTextPresent('ITN011','');
		$this->verifyTextNotPresent('ITN016','');
		$this->clickAndWait("link=Prev Page", "");
		$this->verifyTextNotPresent('ITN005','');
		$this->verifyTextPresent('ITN006','');
		$this->verifyTextNotPresent('ITN011','');
		$this->clickAndWait("link=Prev Page", "");
		$this->verifyTextPresent('ITN001','');
		$this->verifyTextNotPresent('ITN006','');

		// change button count
		$this->type("ctl0\$body\$PageButtonCount", "2");
		$this->clickAndWait("name=ctl0\$body\$ctl6", "");
		$this->clickAndWait("link=Next Page", "");
		$this->verifyTextNotPresent('ITN010','');
		$this->verifyTextPresent('ITN011','');
		$this->verifyTextNotPresent('ITN016','');
		$this->clickAndWait("link=4", "");
		$this->verifyTextNotPresent('ITN015','');
		$this->verifyTextPresent('ITN016','');
		$this->clickAndWait("link=Prev Page", "");
		$this->verifyTextNotPresent('ITN005','');
		$this->verifyTextPresent('ITN006','');
		$this->verifyTextNotPresent('ITN011','');

		$this->type("ctl0\$body\$PageButtonCount", "10");
		$this->clickAndWait("name=ctl0\$body\$ctl6", "");
		$this->type("ctl0\$body\$PageSize", "2");
		$this->clickAndWait("name=ctl0\$body\$ctl8", "");
		$this->verifyTextPresent('ITN001','');
		$this->verifyTextPresent('ITN002','');
		$this->verifyTextNotPresent('ITN003','');
		$this->clickAndWait("link=10", "");
		$this->verifyTextPresent('ITN019','');
		$this->verifyTextNotPresent('ITN018','');
	}
}

?>