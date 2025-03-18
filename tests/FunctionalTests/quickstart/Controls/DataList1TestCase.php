<?php

class QuickstartDataList1TestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TDataList.Sample1&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
		$this->assertSourceContains('ITN001');
		$this->assertSourceContains('$100');
		$this->assertSourceContains('Motherboard');
		$this->assertSourceContains('ITN018');
		$this->assertSourceContains('Surge protector');
		$this->assertSourceContains('45');
		$this->assertSourceContains('$15');
		$this->assertSourceContains('Total 19 products.');
		$this->assertSourceContains('Computer Parts');

		// verify specific table tags
		$this->assertElementPresent("ctl0_body_DataList");
		$this->assertElementPresent("//td[@align='right']");
	}
}
