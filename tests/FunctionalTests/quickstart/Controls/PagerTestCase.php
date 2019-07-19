<?php

class QuickstartPagerTestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TPager.Sample1&amp;notheme=true&amp;lang=en");

		// verify datalist content
		$this->assertSourceContains('ITN001');
		$this->assertSourceContains('ITN002');
		$this->assertSourceNotContains('ITN003');

		// verify numeric paging
		$this->byId("ctl0_body_Pager_ctl1")->click(); // 2nd page
		$this->assertSourceContains('ITN003');
		$this->assertSourceContains('ITN004');
		$this->assertSourceNotContains('ITN002');
		$this->assertSourceNotContains('ITN005');
		$this->byId("ctl0_body_Pager_ctl3")->click(); // 4rd page
		$this->assertSourceContains('ITN007');
		$this->assertSourceContains('ITN008');
		$this->assertSourceNotContains('ITN006');
		$this->assertSourceNotContains('ITN009');
		$this->byId("ctl0_body_Pager_ctl6")->click(); // last page
		$this->assertSourceContains('ITN019');
		$this->assertSourceNotContains('ITN018');
		$this->assertSourceNotContains('ITN001');

		// verify next-prev paging
		$this->byId("ctl0_body_Pager2_ctl1")->click(); // prev page
		$this->assertSourceContains('ITN017');
		$this->assertSourceContains('ITN018');
		$this->assertSourceNotContains('ITN019');
		$this->assertSourceNotContains('ITN016');
		$this->byId("ctl0_body_Pager2_ctl0")->click(); // first page
		$this->assertSourceContains('ITN001');
		$this->assertSourceContains('ITN002');
		$this->assertSourceNotContains('ITN003');
		$this->byId("ctl0_body_Pager2_ctl2")->click(); // next page
		$this->assertSourceContains('ITN003');
		$this->assertSourceContains('ITN004');
		$this->assertSourceNotContains('ITN002');
		$this->assertSourceNotContains('ITN005');

		$this->assertSelected("ctl0_body_Pager3_ctl0", "2");
		$this->selectAndWait("ctl0_body_Pager3_ctl0", "5");
		$this->assertSourceContains('ITN009');
		$this->assertSourceContains('ITN010');
		$this->assertSourceNotContains('ITN008');
		$this->assertSourceNotContains('ITN011');
		$this->selectAndWait("ctl0_body_Pager3_ctl0", "10");
		$this->assertSourceContains('ITN019');
		$this->assertSourceNotContains('ITN018');
	}
}
