<?php

class QuickstartHyperLinkTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.THyperLink.Home&amp;notheme=true&amp;lang=en");
		$this->verifyTitle("PRADO QuickStart Sample", "");
		$this->assertElementPresent("//a[@href=\"http://www.pradosoft.com/\" and @target=\"_blank\"]");
		$this->assertTextPresent("Welcome to", "");
		$this->assertTextPresent("Body contents", "");
		$this->assertElementPresent("//a[img/@alt='Hello World']");
		$this->assertElementPresent("//a[contains(text(),'Body contents')]");
	}
}
