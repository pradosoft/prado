<?php

class Repeater1TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TRepeater.Sample1&amp;notheme=true", "");

		// verify if all required texts are present
		$this->verifyTextPresent('ID','');
		$this->verifyTextPresent('Name','');
		$this->verifyTextPresent('Quantity','');
		$this->verifyTextPresent('Price','');
		$this->verifyTextPresent('Imported','');
		$this->verifyTextPresent('ITN001','');
		$this->verifyTextPresent('Motherboard','');
		$this->verifyTextPresent('Yes','');
		$this->verifyTextPresent('ITN019','');
		$this->verifyTextPresent('Speaker','');
		$this->verifyTextPresent('No','');
		$this->verifyTextPresent('Computer Parts Inventory','');

		// verify specific table tags
		$this->verifyElementPresent("//td[@colspan='5']");
		$this->verifyElementPresent("//table[@cellpadding='2']");
	}
}

?>