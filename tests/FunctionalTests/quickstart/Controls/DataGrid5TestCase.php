<?php

class QuickstartDataGrid5TestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TDataGrid.Sample5&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
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

		// show top pager
		$this->byXPath("//input[@name='ctl0\$body\$ctl1\$c0' and @value='Top']")->click();
		$this->pause(50);
		$this->byId("ctl0_body_DataGrid_ctl8_ctl3")->click();
		$this->pause(50);
		$this->byLinkText("1")->click();
		$this->pause(50);
		// hide top pager
		$this->byXPath("//input[@name='ctl0\$body\$ctl1\$c0' and @value='Top']")->click();
		$this->pause(50);

		// change next prev caption
		$this->type("ctl0\$body\$NextPageText", "Next Page");
		$this->pause(50);
		$this->type("ctl0\$body\$PrevPageText", "Prev Page");
		$this->byXPath("//input[@type='submit' and @value='Submit']")->click();

		// verify next prev paging
		$this->assertSourceContains('ITN001');
		$this->assertSourceNotContains('ITN006');
		$this->byLinkText("Next Page")->click();
		$this->assertSourceNotContains('ITN005');
		$this->assertSourceContains('ITN006');
		$this->assertSourceNotContains('ITN011');
		$this->byLinkText("Next Page")->click();
		$this->assertSourceNotContains('ITN010');
		$this->assertSourceContains('ITN011');
		$this->assertSourceNotContains('ITN016');
		$this->byLinkText("Next Page")->click();
		$this->assertSourceNotContains('ITN015');
		$this->assertSourceContains('ITN016');
		$this->byLinkText("Prev Page")->click();
		$this->assertSourceNotContains('ITN010');
		$this->assertSourceContains('ITN011');
		$this->assertSourceNotContains('ITN016');
		$this->byLinkText("Prev Page")->click();
		$this->assertSourceNotContains('ITN005');
		$this->assertSourceContains('ITN006');
		$this->assertSourceNotContains('ITN011');
		$this->byLinkText("Prev Page")->click();
		$this->assertSourceContains('ITN001');
		$this->assertSourceNotContains('ITN006');

		// change button count
		$this->type("ctl0\$body\$PageButtonCount", "2");
		$this->byName("ctl0\$body\$ctl6")->click();
		$this->pause(50);
		$this->byLinkText("Next Page")->click();
		$this->assertSourceNotContains('ITN010');
		$this->assertSourceContains('ITN011');
		$this->assertSourceNotContains('ITN016');
		$this->byLinkText("4")->click();
		$this->assertSourceNotContains('ITN015');
		$this->assertSourceContains('ITN016');
		$this->byLinkText("Prev Page")->click();
		$this->assertSourceNotContains('ITN005');
		$this->assertSourceContains('ITN006');
		$this->assertSourceNotContains('ITN011');

		$this->type("ctl0\$body\$PageButtonCount", "10");
		$this->byName("ctl0\$body\$ctl6")->click();
		$this->pause(50);
		$this->type("ctl0\$body\$PageSize", "2");
		$this->pause(50);
		$this->byName("ctl0\$body\$ctl8")->click();
		$this->assertSourceContains('ITN001');
		$this->assertSourceContains('ITN002');
		$this->assertSourceNotContains('ITN003');
		$this->byLinkText("10")->click();
		$this->assertSourceContains('ITN019');
		$this->assertSourceNotContains('ITN018');
	}
}
