<?php

class QuickstartHyperLinkTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.THyperLink.Home&amp;notheme=true&amp;lang=en");
		$this->assertEquals("PRADO QuickStart Sample", $this->title());
		$this->assertElementPresent("//a[@href=\"https://github.com/pradosoft/prado/\" and @target=\"_blank\"]");
		$this->assertSourceContains("Welcome to");
		$this->assertSourceContains("Body contents");
		$this->assertElementPresent("//a[img/@alt='Hello World']");
		$this->assertElementPresent("//a[contains(text(),'Body contents')]");
	}
}
