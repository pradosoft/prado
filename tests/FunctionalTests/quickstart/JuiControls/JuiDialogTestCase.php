<?php


/**
 * Testcase for TJuiDialog
 */
class JuiDialogTestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=JuiControls.Samples.TJuiDialog.Home&amp;notheme=true&amp;lang=en");

		$this->assertEquals("PRADO QuickStart Sample", $this->title());

		$this->assertSourceContains('TJuiDialog Samples');

		$base = 'ctl0_body_';


		$this->byId("${base}ctl0")->click();
		$this->pause(500);
		$this->assertVisible("${base}dlg1");

		$this->active()->click(); // close


		$this->assertEmpty($this->byId("${base}lbl3")->text());
		$this->byId("${base}ctl2")->click();
		$this->pause(500);
		$this->assertVisible("${base}dlg3");

		// Click OK (by keys...)
		$this->keys(\PHPUnit\Extensions\Selenium2TestCase\Keys::ENTER);
		$this->pause(500);
		$this->assertEquals('Button Ok clicked', $this->byId("${base}lbl3")->text());
	}
}
