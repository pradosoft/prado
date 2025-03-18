<?php

class QuickstartDataGrid2TestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TDataGrid.Sample2&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
		$this->assertSourceContains('Book Title');
		$this->assertSourceContains('Publisher');
		$this->assertSourceContains('Price');
		$this->assertSourceContains('In-stock');
		$this->assertSourceContains('Rating');

		// verify book titles
		$this->assertElementPresent("//a[@href='http://www.amazon.com/gp/product/0596007124' and text()='Head First Design Patterns']", '');
		$this->assertElementPresent("//a[@href='http://www.amazon.com/gp/product/0321278658' and text()='Extreme Programming Explained : Embrace Change']", '');

		// verify publishers
		$this->assertStringContainsString("O'Reilly Media, Inc.", $this->source());
		$this->assertSourceContains("Addison-Wesley Professional");

		// verify prices
		$this->assertSourceContains("\$37.49");
		$this->assertSourceContains("\$38.49");

		// verify in-stock
		$this->assertAttribute('ctl0_body_DataGrid_ctl1_ctl5@checked', 'regexp:true|checked');
		$this->assertAttribute('ctl0_body_DataGrid_ctl1_ctl5@disabled', 'regexp:true|disabled');
		$this->assertAttribute('ctl0_body_DataGrid_ctl2_ctl5@checked', 'regexp:true|checked');
		$this->assertAttribute('ctl0_body_DataGrid_ctl6_ctl5@checked', null);
		$this->assertAttribute('ctl0_body_DataGrid_ctl6_ctl5@disabled', 'regexp:true|disabled');

		// verify ratings
		//$this->assertElementPresent("//img[@src='images/star5.gif']",'');
		//$this->assertElementPresent("//img[@src='images/star2.gif']",'');

		// verify toggle column visibility
		$this->byXPath("//input[@name='ctl0\$body\$ctl1\$c0' and @value='Book Title']")->click();
		$this->assertSourceNotContains('Head First Design Patterns');
		$this->byXPath("//input[@name='ctl0\$body\$ctl1\$c3' and @value='In-stock']")->click();
		$this->pause(50);
		$this->assertElementNotPresent('ctl0_body_DataGrid_ctl1_ctl5', '');
		$this->byXPath("//input[@name='ctl0\$body\$ctl1\$c3' and @value='In-stock']")->click();
		$this->pause(50);
		$this->assertElementPresent('ctl0_body_DataGrid_ctl1_ctl5', '');
		$this->byXPath("//input[@name='ctl0\$body\$ctl1\$c0' and @value='Book Title']")->click();
		$this->assertSourceContains('Head First Design Patterns');
	}
}
