<?php

class QuickstartPanelTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TPanel.Home&amp;notheme=true&amp;lang=en");
		$this->assertContains("This is panel content with", $this->source());
		$this->assertElementPresent("//span[text()='label']");
		$this->assertContains("grouping text", $this->source());
		$this->byXPath("//input[@name='ctl0\$body\$ctl17']")->click();
		$this->assertNotContains("You have clicked on 'button2'.", $this->source());
		$this->byXPath("//input[@type='submit' and @value='button2']")->click();
		$this->assertContains("You have clicked on 'button2'.", $this->source());
	}
}
