<?php

class QuickstartDataGrid6TestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TDataGrid.Sample6&amp;notheme=true&amp;lang=en");

		// verify column headers
		$this->assertSourceContains('id');
		$this->assertSourceContains('name');
		$this->assertSourceContains('quantity');
		$this->assertSourceContains('price');
		$this->assertSourceContains('imported');

		$this->assertSourceContains('ITN001');
		$this->assertSourceContains('ITN002');
		$this->assertSourceContains('ITN003');
		$this->assertSourceContains('ITN004');
		$this->assertSourceContains('ITN005');
		$this->assertSourceNotContains('ITN006');

		// verify paging
		$this->byLinkText("2")->click();
		$this->assertSourceContains('ITN006');
		$this->assertSourceContains('ITN007');
		$this->assertSourceContains('ITN008');
		$this->assertSourceContains('ITN009');
		$this->assertSourceContains('ITN010');
		$this->assertSourceNotContains('ITN011');
		$this->assertSourceNotContains('ITN005');

		$this->byLinkText("4")->click();
		$this->assertSourceContains('ITN016');
		$this->assertSourceContains('ITN017');
		$this->assertSourceContains('ITN018');
		$this->assertSourceContains('ITN019');
		$this->assertSourceNotContains('ITN015');

		$this->byLinkText("1")->click();
		$this->assertSourceContains('ITN001');
		$this->assertSourceContains('ITN002');
		$this->assertSourceContains('ITN003');
		$this->assertSourceContains('ITN004');
		$this->assertSourceContains('ITN005');
		$this->assertSourceNotContains('ITN006');
	}
}
