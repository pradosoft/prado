<?php

class PanelTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TPanel.Home&amp;notheme=true&amp;lang=en", "");
		$this->verifyTextPresent("This is panel content with", "");
		$this->verifyElementPresent("//span[text()='label']");
		$this->verifyTextPresent("grouping text", "");
		$this->click("//input[@name='ctl0\$body\$ctl17']", "");
		$this->verifyTextNotPresent("You have clicked on 'button2'.");
		$this->clickAndWait("//input[@type='submit' and @value='button2']", "");
		$this->verifyTextPresent("You have clicked on 'button2'.");
	}
}

?>