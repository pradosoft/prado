<?php

class QuickstartRepeater1TestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TRepeater.Sample1&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
		$this->assertSourceContains('ID');
		$this->assertSourceContains('Name');
		$this->assertSourceContains('Quantity');
		$this->assertSourceContains('Price');
		$this->assertSourceContains('Imported');
		$this->assertSourceContains('ITN001');
		$this->assertSourceContains('Motherboard');
		$this->assertSourceContains('Yes');
		$this->assertSourceContains('ITN019');
		$this->assertSourceContains('Speaker');
		$this->assertSourceContains('No');
		$this->assertSourceContains('Computer Parts Inventory');

		// verify specific table tags
		$this->assertElementPresent("//td[@colspan='5']");
		$this->assertElementPresent("//table[@cellpadding='2']");
	}
}
