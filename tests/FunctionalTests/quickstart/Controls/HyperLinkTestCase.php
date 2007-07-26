<?php

class HyperLinkTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.THyperLink.Home&amp;notheme=true&amp;lang=en", "");
		$this->verifyTitle("PRADO QuickStart Sample", "");
		$this->verifyElementPresent("//a[@href=\"http://www.pradosoft.com/\" and @target=\"_blank\"]");
		$this->verifyTextPresent("Welcome to", "");
		$this->verifyTextPresent("Body contents", "");
		$this->verifyElementPresent("//a[img/@alt='Hello World']");
		$this->verifyElementPresent("//a[contains(text(),'Body contents')]");
	}
}

?>