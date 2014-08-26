<?php

class QuickstartRepeater1TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TRepeater.Sample1&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
		$this->assertContains('ID', $this->source());
		$this->assertContains('Name', $this->source());
		$this->assertContains('Quantity', $this->source());
		$this->assertContains('Price', $this->source());
		$this->assertContains('Imported', $this->source());
		$this->assertContains('ITN001', $this->source());
		$this->assertContains('Motherboard', $this->source());
		$this->assertContains('Yes', $this->source());
		$this->assertContains('ITN019', $this->source());
		$this->assertContains('Speaker', $this->source());
		$this->assertContains('No', $this->source());
		$this->assertContains('Computer Parts Inventory', $this->source());

		// verify specific table tags
		$this->assertElementPresent("//td[@colspan='5']");
		$this->assertElementPresent("//table[@cellpadding='2']");
	}
}
