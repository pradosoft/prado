<?php

class QuickstartPagerTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TPager.Sample1&amp;notheme=true&amp;lang=en");

		// verify datalist content
		$this->assertTextPresent('ITN001','');
		$this->assertTextPresent('ITN002','');
		$this->assertTextNotPresent('ITN003','');

		// verify numeric paging
		$this->clickAndWait("ctl0_body_Pager_ctl1", ""); // 2nd page
		$this->assertTextPresent('ITN003','');
		$this->assertTextPresent('ITN004','');
		$this->assertTextNotPresent('ITN002','');
		$this->assertTextNotPresent('ITN005','');
		$this->clickAndWait("ctl0_body_Pager_ctl3", ""); // 4rd page
		$this->assertTextPresent('ITN007','');
		$this->assertTextPresent('ITN008','');
		$this->assertTextNotPresent('ITN006','');
		$this->assertTextNotPresent('ITN009','');
		$this->clickAndWait("ctl0_body_Pager_ctl6", ""); // last page
		$this->assertTextPresent('ITN019','');
		$this->assertTextNotPresent('ITN018','');
		$this->assertTextNotPresent('ITN001','');

		// verify next-prev paging
		$this->clickAndWait("ctl0_body_Pager2_ctl1", ""); // prev page
		$this->assertTextPresent('ITN017','');
		$this->assertTextPresent('ITN018','');
		$this->assertTextNotPresent('ITN019','');
		$this->assertTextNotPresent('ITN016','');
		$this->clickAndWait("ctl0_body_Pager2_ctl0", ""); // first page
		$this->assertTextPresent('ITN001','');
		$this->assertTextPresent('ITN002','');
		$this->assertTextNotPresent('ITN003','');
		$this->clickAndWait("ctl0_body_Pager2_ctl2", ""); // next page
		$this->assertTextPresent('ITN003','');
		$this->assertTextPresent('ITN004','');
		$this->assertTextNotPresent('ITN002','');
		$this->assertTextNotPresent('ITN005','');

		$this->assertSelected("ctl0_body_Pager3_ctl0","2");
		$this->selectAndWait("ctl0_body_Pager3_ctl0", "label=5");
		$this->assertTextPresent('ITN009','');
		$this->assertTextPresent('ITN010','');
		$this->assertTextNotPresent('ITN008','');
		$this->assertTextNotPresent('ITN011','');
		$this->selectAndWait("ctl0_body_Pager3_ctl0", "label=10");
		$this->assertTextPresent('ITN019','');
		$this->assertTextNotPresent('ITN018','');
	}
}
