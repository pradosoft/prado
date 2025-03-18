<?php

class QuickstartDataGrid4TestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TDataGrid.Sample4&amp;notheme=true&amp;lang=en");

		// verify the 2nd row of data
		$this->assertSourceContains("Design Patterns: Elements of Reusable Object-Oriented Software");
		$this->assertSourceContains("Addison-Wesley Professional");
		$this->assertSourceContains("$47.04");
		$this->assertAttribute('ctl0_body_DataGrid_ctl2_ctl5@checked', 'regexp:true|checked');
		$this->assertAttribute('ctl0_body_DataGrid_ctl2_ctl5@disabled', 'regexp:true|disabled');

		// verify sorting
		$this->byLinkText("Book Title")->click();
		$this->pause(50);
		$this->assertAttribute('ctl0_body_DataGrid_ctl1_ctl5@checked', null);
		$this->byLinkText("Publisher")->click();
		$this->pause(50);
		$this->assertAttribute('ctl0_body_DataGrid_ctl6_ctl5@checked', null);
		$this->byLinkText("Price")->click();
		$this->pause(50);
		$this->assertAttribute('ctl0_body_DataGrid_ctl2_ctl5@checked', null);
		$this->byLinkText("In-stock")->click();
		$this->pause(50);
		$this->assertAttribute('ctl0_body_DataGrid_ctl1_ctl5@checked', null);
		$this->byLinkText("Rating")->click();
		$this->pause(50);
		$this->assertAttribute('ctl0_body_DataGrid_ctl6_ctl5@checked', null);
	}
}
