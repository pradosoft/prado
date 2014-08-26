<?php

class QuickstartDataGrid5TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TDataGrid.Sample5&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
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

		// show top pager
		$this->byXPath("//input[@name='ctl0\$body\$ctl1\$c0' and @value='Top']")->click();
		$this->byId("ctl0_body_DataGrid_ctl8_ctl3")->click();
		$this->byLinkText("1")->click();
		// hide top pager
		$this->byXPath("//input[@name='ctl0\$body\$ctl1\$c0' and @value='Top']")->click();

		// change next prev caption
		$this->type("ctl0\$body\$NextPageText", "Next Page");
		$this->type("ctl0\$body\$PrevPageText", "Prev Page");
		$this->byXPath("//input[@type='submit' and @value='Submit']")->click();

		// verify next prev paging
		$this->assertContains('ITN001', $this->source());
		$this->assertNotContains('ITN006', $this->source());
		$this->byLinkText("Next Page")->click();
		$this->assertNotContains('ITN005', $this->source());
		$this->assertContains('ITN006', $this->source());
		$this->assertNotContains('ITN011', $this->source());
		$this->byLinkText("Next Page")->click();
		$this->assertNotContains('ITN010', $this->source());
		$this->assertContains('ITN011', $this->source());
		$this->assertNotContains('ITN016', $this->source());
		$this->byLinkText("Next Page")->click();
		$this->assertNotContains('ITN015', $this->source());
		$this->assertContains('ITN016', $this->source());
		$this->byLinkText("Prev Page")->click();
		$this->assertNotContains('ITN010', $this->source());
		$this->assertContains('ITN011', $this->source());
		$this->assertNotContains('ITN016', $this->source());
		$this->byLinkText("Prev Page")->click();
		$this->assertNotContains('ITN005', $this->source());
		$this->assertContains('ITN006', $this->source());
		$this->assertNotContains('ITN011', $this->source());
		$this->byLinkText("Prev Page")->click();
		$this->assertContains('ITN001', $this->source());
		$this->assertNotContains('ITN006', $this->source());

		// change button count
		$this->type("ctl0\$body\$PageButtonCount", "2");
		$this->byName("ctl0\$body\$ctl6")->click();
		$this->byLinkText("Next Page")->click();
		$this->assertNotContains('ITN010', $this->source());
		$this->assertContains('ITN011', $this->source());
		$this->assertNotContains('ITN016', $this->source());
		$this->byLinkText("4")->click();
		$this->assertNotContains('ITN015', $this->source());
		$this->assertContains('ITN016', $this->source());
		$this->byLinkText("Prev Page")->click();
		$this->assertNotContains('ITN005', $this->source());
		$this->assertContains('ITN006', $this->source());
		$this->assertNotContains('ITN011', $this->source());

		$this->type("ctl0\$body\$PageButtonCount", "10");
		$this->byName("ctl0\$body\$ctl6")->click();
		$this->type("ctl0\$body\$PageSize", "2");
		$this->byName("ctl0\$body\$ctl8")->click();
		$this->assertContains('ITN001', $this->source());
		$this->assertContains('ITN002', $this->source());
		$this->assertNotContains('ITN003', $this->source());
		$this->byLinkText("10")->click();
		$this->assertContains('ITN019', $this->source());
		$this->assertNotContains('ITN018', $this->source());
	}
}
