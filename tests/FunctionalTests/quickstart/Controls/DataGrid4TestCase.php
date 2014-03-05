<?php

class QuickstartDataGrid4TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TDataGrid.Sample4&amp;notheme=true&amp;lang=en");

		// verify the 2nd row of data
		$this->assertContains("Design Patterns: Elements of Reusable Object-Oriented Software", $this->source());
		$this->assertContains("Addison-Wesley Professional", $this->source());
		$this->assertContains("$47.04", $this->source());
		$this->assertAttribute('ctl0_body_DataGrid_ctl2_ctl5@checked','regexp:true|checked');
		$this->assertAttribute('ctl0_body_DataGrid_ctl2_ctl5@disabled','regexp:true|disabled');

		// verify sorting
		$this->byLinkText("Book Title")->click();
		$this->assertAttribute('ctl0_body_DataGrid_ctl1_ctl5@checked', null);
		$this->byLinkText("Publisher")->click();
		$this->assertAttribute('ctl0_body_DataGrid_ctl6_ctl5@checked', null);
		$this->byLinkText("Price")->click();
		$this->assertAttribute('ctl0_body_DataGrid_ctl2_ctl5@checked', null);
		$this->byLinkText("In-stock")->click();
		$this->assertAttribute('ctl0_body_DataGrid_ctl1_ctl5@checked', null);
		$this->byLinkText("Rating")->click();
		$this->assertAttribute('ctl0_body_DataGrid_ctl4_ctl5@checked', null);
	}
}
