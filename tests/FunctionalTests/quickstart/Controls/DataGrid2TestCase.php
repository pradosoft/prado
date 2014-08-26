<?php

class QuickstartDataGrid2TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TDataGrid.Sample2&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
		$this->assertContains('Book Title', $this->source());
		$this->assertContains('Publisher', $this->source());
		$this->assertContains('Price', $this->source());
		$this->assertContains('In-stock', $this->source());
		$this->assertContains('Rating', $this->source());

		// verify book titles
		$this->assertElementPresent("//a[@href='http://www.amazon.com/gp/product/0596007124' and text()='Head First Design Patterns']",'');
		$this->assertElementPresent("//a[@href='http://www.amazon.com/gp/product/0321278658' and text()='Extreme Programming Explained : Embrace Change']",'');

		// verify publishers
		$this->assertContains("O'Reilly Media, Inc.", $this->source());
		$this->assertContains("Addison-Wesley Professional", $this->source());

		// verify prices
		$this->assertContains("\$37.49", $this->source());
		$this->assertContains("\$38.49", $this->source());

		// verify in-stock
		$this->assertAttribute('ctl0_body_DataGrid_ctl1_ctl5@checked','regexp:true|checked');
		$this->assertAttribute('ctl0_body_DataGrid_ctl1_ctl5@disabled','regexp:true|disabled');
		$this->assertAttribute('ctl0_body_DataGrid_ctl2_ctl5@checked','regexp:true|checked');
		$this->assertAttribute('ctl0_body_DataGrid_ctl6_ctl5@checked',null);
		$this->assertAttribute('ctl0_body_DataGrid_ctl6_ctl5@disabled','regexp:true|disabled');

		// verify ratings
		//$this->assertElementPresent("//img[@src='images/star5.gif']",'');
		//$this->assertElementPresent("//img[@src='images/star2.gif']",'');

		// verify toggle column visibility
		$this->byXPath("//input[@name='ctl0\$body\$ctl1\$c0' and @value='Book Title']")->click();
		$this->assertNotContains('Head First Design Patterns', $this->source());
		$this->byXPath("//input[@name='ctl0\$body\$ctl1\$c3' and @value='In-stock']")->click();
		$this->assertElementNotPresent('ctl0_body_DataGrid_ctl1_ctl5','');
		$this->byXPath("//input[@name='ctl0\$body\$ctl1\$c3' and @value='In-stock']")->click();
		$this->assertElementPresent('ctl0_body_DataGrid_ctl1_ctl5','');
		$this->byXPath("//input[@name='ctl0\$body\$ctl1\$c0' and @value='Book Title']")->click();
		$this->assertContains('Head First Design Patterns', $this->source());
	}
}
