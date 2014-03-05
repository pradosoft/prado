<?php

class QuickstartDataList1TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TDataList.Sample1&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
		$this->assertContains('ITN001', $this->source());
		$this->assertContains('$100', $this->source());
		$this->assertContains('Motherboard', $this->source());
		$this->assertContains('ITN018', $this->source());
		$this->assertContains('Surge protector', $this->source());
		$this->assertContains('45', $this->source());
		$this->assertContains('$15', $this->source());
		$this->assertContains('Total 19 products.', $this->source());
		$this->assertContains('Computer Parts', $this->source());

		// verify specific table tags
		$this->assertElementPresent("ctl0_body_DataList");
		$this->assertElementPresent("//td[@align='right']");
	}
}
