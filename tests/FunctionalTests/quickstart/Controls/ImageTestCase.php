<?php

//New Test
class QuickstartImageTestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TImage.Home&amp;notheme=true&amp;lang=en");

		$this->assertEquals("PRADO QuickStart Sample", $this->title());

		//$this->assertElementPresent("//img[contains(@src,'/hello_world.gif') and @alt='']");
		$this->assertElementPresent("//img[contains(@src,'/hello_world.gif') and @alt='Hello World!']");
		$this->assertSourceContains("Hello World! Hello World! Hello World!");
		//$this->assertElementPresent("//img[contains(@src,'/hello_world.gif') and @align='baseline']");
		//$this->assertElementPresent("//img[contains(@src,'/hello_world.gif') and contains(@longdesc,'HelloWorld.html')]");
	}
}
