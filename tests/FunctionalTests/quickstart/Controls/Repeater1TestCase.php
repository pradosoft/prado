<?php

class QuickstartRepeater1TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TRepeater.Sample1&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
		$this->assertTextPresent('ID','');
		$this->assertTextPresent('Name','');
		$this->assertTextPresent('Quantity','');
		$this->assertTextPresent('Price','');
		$this->assertTextPresent('Imported','');
		$this->assertTextPresent('ITN001','');
		$this->assertTextPresent('Motherboard','');
		$this->assertTextPresent('Yes','');
		$this->assertTextPresent('ITN019','');
		$this->assertTextPresent('Speaker','');
		$this->assertTextPresent('No','');
		$this->assertTextPresent('Computer Parts Inventory','');

		// verify specific table tags
		$this->assertElementPresent("//td[@colspan='5']");
		$this->assertElementPresent("//table[@cellpadding='2']");
	}
}
