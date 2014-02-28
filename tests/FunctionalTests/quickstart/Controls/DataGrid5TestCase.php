<?php

class QuickstartDataGrid5TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TDataGrid.Sample5&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
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
		$this->assertTextPresent('ITN001','');
		$this->assertTextNotPresent('ITN006','');
		$this->clickAndWait("link=Next Page", "");
		$this->assertTextNotPresent('ITN005','');
		$this->assertTextPresent('ITN006','');
		$this->assertTextNotPresent('ITN011','');
		$this->clickAndWait("link=Next Page", "");
		$this->assertTextNotPresent('ITN010','');
		$this->assertTextPresent('ITN011','');
		$this->assertTextNotPresent('ITN016','');
		$this->clickAndWait("link=Next Page", "");
		$this->assertTextNotPresent('ITN015','');
		$this->assertTextPresent('ITN016','');
		$this->clickAndWait("link=Prev Page", "");
		$this->assertTextNotPresent('ITN010','');
		$this->assertTextPresent('ITN011','');
		$this->assertTextNotPresent('ITN016','');
		$this->clickAndWait("link=Prev Page", "");
		$this->assertTextNotPresent('ITN005','');
		$this->assertTextPresent('ITN006','');
		$this->assertTextNotPresent('ITN011','');
		$this->clickAndWait("link=Prev Page", "");
		$this->assertTextPresent('ITN001','');
		$this->assertTextNotPresent('ITN006','');

		// change button count
		$this->type("ctl0\$body\$PageButtonCount", "2");
		$this->clickAndWait("name=ctl0\$body\$ctl6", "");
		$this->clickAndWait("link=Next Page", "");
		$this->assertTextNotPresent('ITN010','');
		$this->assertTextPresent('ITN011','');
		$this->assertTextNotPresent('ITN016','');
		$this->clickAndWait("link=4", "");
		$this->assertTextNotPresent('ITN015','');
		$this->assertTextPresent('ITN016','');
		$this->clickAndWait("link=Prev Page", "");
		$this->assertTextNotPresent('ITN005','');
		$this->assertTextPresent('ITN006','');
		$this->assertTextNotPresent('ITN011','');

		$this->type("ctl0\$body\$PageButtonCount", "10");
		$this->clickAndWait("name=ctl0\$body\$ctl6", "");
		$this->type("ctl0\$body\$PageSize", "2");
		$this->clickAndWait("name=ctl0\$body\$ctl8", "");
		$this->assertTextPresent('ITN001','');
		$this->assertTextPresent('ITN002','');
		$this->assertTextNotPresent('ITN003','');
		$this->clickAndWait("link=10", "");
		$this->assertTextPresent('ITN019','');
		$this->assertTextNotPresent('ITN018','');
	}
}
