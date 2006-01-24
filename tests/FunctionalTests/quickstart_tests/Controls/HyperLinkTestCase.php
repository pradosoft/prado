<?php

class HyperLinkTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.THyperLink.Home&functionaltest=true", "");
		$this->verifyTitle("PRADO QuickStart Sample", "");
		//$this->verifyAttribute("//a[@href=\"http://www.pradosoft.com/\"]/@target","_blank");
		$this->verifyHTMLPresent('<a href="http://www.pradosoft.com/" target="_blank" style="background-color:silver;font-style:italic;">PradoSoft.com</a>');
		$this->verifyTextPresent("Welcome to", "");
		$this->verifyTextPresent("Body contents", "");
		$this->verifyElementPresent("//a[img/@alt='Hello World']");
		$this->verifyElementPresent("//a[contains(text(),'Body contents')]");
	}
}

?>