<?php

class QuickstartPanelTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TPanel.Home&amp;notheme=true&amp;lang=en");
		$this->assertSourceContains("This is panel content with");
		$this->assertElementPresent("//span[text()='label']");
		$this->assertSourceContains("grouping text");
		$this->byXPath("//input[@name='ctl0\$body\$ctl17']")->click();
		$this->assertSourceNotContains("You have clicked on 'button2'.");
		$this->byXPath("//input[@type='submit' and @value='button2']")->click();
		$this->assertSourceContains("You have clicked on 'button2'.");
	}
}
