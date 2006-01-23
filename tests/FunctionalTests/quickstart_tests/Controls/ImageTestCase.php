<?php

//New Test
class ImageTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TImage.Home", "");
		$this->verifyTitle("PRADO QuickStart Sample", "");
		$this->verifyElementPresent("//img[contains(@src,'/hello_world.gif') and @alt='']");
		$this->verifyElementPresent("//img[contains(@src,'/hello_world.gif') and @alt='Hello World!']");
		$this->verifyTextPresent("Hello World! Hello World! Hello World!", "");
		$this->verifyElementPresent("//img[contains(@src,'/hello_world.gif') and @align='baseline']");
		$this->verifyElementPresent("//img[contains(@src,'/hello_world.gif') and contains(@longdesc,'HelloWorld.html')]");
	}
}

?>