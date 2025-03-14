<?php

use Facebook\WebDriver\WebDriverKeys;

/**
 * Testcase for TJuiDialog
 */
class JuiDialogTestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=JuiControls.Samples.TJuiDialog.Home&amp;notheme=true&amp;lang=en");

		$this->assertTitle("PRADO QuickStart Sample");

		$this->assertSourceContains('TJuiDialog Samples');

		$base = 'ctl0_body_';


		$this->byId("${base}ctl0")->click();
		$this->assertVisible("${base}dlg1");

		$this->active()->click(); // close


		$this->assertText("${base}lbl3", '');
		$this->byId("${base}ctl2")->click();
		$this->assertVisible("${base}dlg3");

		// Click OK (by keys...)
		$this->keys(WebDriverKeys::ENTER);
		$this->assertText("${base}lbl3", 'Button Ok clicked');
	}
}
