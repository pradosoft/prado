<?php

class QuickstartDataGrid6TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TDataGrid.Sample6&amp;notheme=true&amp;lang=en");

		// verify column headers
		$this->assertContains('id', $this->source());
		$this->assertContains('name', $this->source());
		$this->assertContains('quantity', $this->source());
		$this->assertContains('price', $this->source());
		$this->assertContains('imported', $this->source());

		$this->assertContains('ITN001', $this->source());
		$this->assertContains('ITN002', $this->source());
		$this->assertContains('ITN003', $this->source());
		$this->assertContains('ITN004', $this->source());
		$this->assertContains('ITN005', $this->source());
		$this->assertNotContains('ITN006', $this->source());

		// verify paging
		$this->byLinkText("2")->click();
		$this->assertContains('ITN006', $this->source());
		$this->assertContains('ITN007', $this->source());
		$this->assertContains('ITN008', $this->source());
		$this->assertContains('ITN009', $this->source());
		$this->assertContains('ITN010', $this->source());
		$this->assertNotContains('ITN011', $this->source());
		$this->assertNotContains('ITN005', $this->source());

		$this->byLinkText("4")->click();
		$this->assertContains('ITN016', $this->source());
		$this->assertContains('ITN017', $this->source());
		$this->assertContains('ITN018', $this->source());
		$this->assertContains('ITN019', $this->source());
		$this->assertNotContains('ITN015', $this->source());

		$this->byLinkText("1")->click();
		$this->assertContains('ITN001', $this->source());
		$this->assertContains('ITN002', $this->source());
		$this->assertContains('ITN003', $this->source());
		$this->assertContains('ITN004', $this->source());
		$this->assertContains('ITN005', $this->source());
		$this->assertNotContains('ITN006', $this->source());
	}
}
