<?php

class QuickstartPagerTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TPager.Sample1&amp;notheme=true&amp;lang=en");

		// verify datalist content
		$this->assertContains('ITN001', $this->source());
		$this->assertContains('ITN002', $this->source());
		$this->assertNotContains('ITN003', $this->source());

		// verify numeric paging
		$this->byId("ctl0_body_Pager_ctl1")->click(); // 2nd page
		$this->assertContains('ITN003', $this->source());
		$this->assertContains('ITN004', $this->source());
		$this->assertNotContains('ITN002', $this->source());
		$this->assertNotContains('ITN005', $this->source());
		$this->byId("ctl0_body_Pager_ctl3")->click(); // 4rd page
		$this->assertContains('ITN007', $this->source());
		$this->assertContains('ITN008', $this->source());
		$this->assertNotContains('ITN006', $this->source());
		$this->assertNotContains('ITN009', $this->source());
		$this->byId("ctl0_body_Pager_ctl6")->click(); // last page
		$this->assertContains('ITN019', $this->source());
		$this->assertNotContains('ITN018', $this->source());
		$this->assertNotContains('ITN001', $this->source());

		// verify next-prev paging
		$this->byId("ctl0_body_Pager2_ctl1")->click(); // prev page
		$this->assertContains('ITN017', $this->source());
		$this->assertContains('ITN018', $this->source());
		$this->assertNotContains('ITN019', $this->source());
		$this->assertNotContains('ITN016', $this->source());
		$this->byId("ctl0_body_Pager2_ctl0")->click(); // first page
		$this->assertContains('ITN001', $this->source());
		$this->assertContains('ITN002', $this->source());
		$this->assertNotContains('ITN003', $this->source());
		$this->byId("ctl0_body_Pager2_ctl2")->click(); // next page
		$this->assertContains('ITN003', $this->source());
		$this->assertContains('ITN004', $this->source());
		$this->assertNotContains('ITN002', $this->source());
		$this->assertNotContains('ITN005', $this->source());

		$this->assertSelected("ctl0_body_Pager3_ctl0","2");
		$this->selectAndWait("ctl0_body_Pager3_ctl0", "5");
		$this->assertContains('ITN009', $this->source());
		$this->assertContains('ITN010', $this->source());
		$this->assertNotContains('ITN008', $this->source());
		$this->assertNotContains('ITN011', $this->source());
		$this->selectAndWait("ctl0_body_Pager3_ctl0", "10");
		$this->assertContains('ITN019', $this->source());
		$this->assertNotContains('ITN018', $this->source());
	}
}
