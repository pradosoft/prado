<?php

class QuickstartDataGrid1TestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TDataGrid.Sample1&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
		$this->assertSourceContains('id');
		$this->assertSourceContains('name');
		$this->assertSourceContains('quantity');
		$this->assertSourceContains('price');
		$this->assertSourceContains('imported');
		$this->assertSourceContains('ITN001');
		$this->assertSourceContains('Motherboard');
		$this->assertSourceContains('100');
		$this->assertSourceContains('true');
		$this->assertSourceContains('ITN019');
		$this->assertSourceContains('Speaker');
		$this->assertSourceContains('35');
		$this->assertSourceContains('65');
		$this->assertSourceContains('false');

		// verify specific table tags
		$this->assertElementPresent("ctl0_body_DataGrid");
		$this->assertAttribute("ctl0_body_DataGrid@cellpadding", "2");
	}
}
