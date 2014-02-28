<?php

class QuickstartPanelTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TPanel.Home&amp;notheme=true&amp;lang=en");
		$this->assertTextPresent("This is panel content with", "");
		$this->assertElementPresent("//span[text()='label']");
		$this->assertTextPresent("grouping text", "");
		$this->click("//input[@name='ctl0\$body\$ctl17']", "");
		$this->assertTextNotPresent("You have clicked on 'button2'.");
		$this->clickAndWait("//input[@type='submit' and @value='button2']", "");
		$this->assertTextPresent("You have clicked on 'button2'.");
	}
}
