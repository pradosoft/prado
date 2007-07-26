<?php

class PagerTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TPager.Sample1&amp;notheme=true&amp;lang=en", "");

		// verify datalist content
		$this->verifyTextPresent('ITN001','');
		$this->verifyTextPresent('ITN002','');
		$this->verifyTextNotPresent('ITN003','');

		// verify numeric paging
		$this->clickAndWait("ctl0_body_Pager_ctl1", ""); // 2nd page
		$this->verifyTextPresent('ITN003','');
		$this->verifyTextPresent('ITN004','');
		$this->verifyTextNotPresent('ITN002','');
		$this->verifyTextNotPresent('ITN005','');
		$this->clickAndWait("ctl0_body_Pager_ctl3", ""); // 4rd page
		$this->verifyTextPresent('ITN007','');
		$this->verifyTextPresent('ITN008','');
		$this->verifyTextNotPresent('ITN006','');
		$this->verifyTextNotPresent('ITN009','');
		$this->clickAndWait("ctl0_body_Pager_ctl6", ""); // last page
		$this->verifyTextPresent('ITN019','');
		$this->verifyTextNotPresent('ITN018','');
		$this->verifyTextNotPresent('ITN001','');

		// verify next-prev paging
		$this->clickAndWait("ctl0_body_Pager2_ctl1", ""); // prev page
		$this->verifyTextPresent('ITN017','');
		$this->verifyTextPresent('ITN018','');
		$this->verifyTextNotPresent('ITN019','');
		$this->verifyTextNotPresent('ITN016','');
		$this->clickAndWait("ctl0_body_Pager2_ctl0", ""); // first page
		$this->verifyTextPresent('ITN001','');
		$this->verifyTextPresent('ITN002','');
		$this->verifyTextNotPresent('ITN003','');
		$this->clickAndWait("ctl0_body_Pager2_ctl2", ""); // next page
		$this->verifyTextPresent('ITN003','');
		$this->verifyTextPresent('ITN004','');
		$this->verifyTextNotPresent('ITN002','');
		$this->verifyTextNotPresent('ITN005','');

		$this->verifySelected("ctl0_body_Pager3_ctl0","label=2");
		$this->selectAndWait("ctl0_body_Pager3_ctl0", "label=5");
		$this->verifyTextPresent('ITN009','');
		$this->verifyTextPresent('ITN010','');
		$this->verifyTextNotPresent('ITN008','');
		$this->verifyTextNotPresent('ITN011','');
		$this->selectAndWait("ctl0_body_Pager3_ctl0", "label=10");
		$this->verifyTextPresent('ITN019','');
		$this->verifyTextNotPresent('ITN018','');
	}
}

?>