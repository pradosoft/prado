<?php

class TabPanelTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TTabPanel.Home&amp;notheme=true", "");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// verify initial visibility
		$this->verifyNotVisible('ctl0_body_View1');		// view 1
		$this->verifyVisible('ctl0_body_View2');		// view 2
		$this->verifyNotVisible('ctl0_body_ctl2');		// view 3

		// switching to the first view
		$this->click('ctl0_body_View1_0');
		$this->pause(500);
		$this->verifyVisible('ctl0_body_View1');		// view 1
		$this->verifyNotVisible('ctl0_body_View2');		// view 2
		$this->verifyNotVisible('ctl0_body_ctl2');		// view 3
		$this->verifyNotVisible('ctl0_body_View11');	// view 11
		$this->verifyVisible('ctl0_body_View21');		// view 21

		// switching to View11
		$this->click('ctl0_body_View11_0');
		$this->pause(500);
		$this->verifyVisible('ctl0_body_View1');		// view 1
		$this->verifyNotVisible('ctl0_body_View2');		// view 2
		$this->verifyNotVisible('ctl0_body_ctl2');		// view 3
		$this->verifyVisible('ctl0_body_View11');		// view 11
		$this->verifyNotVisible('ctl0_body_View21');	// view 21

		// switching to the third view
		$this->click('ctl0_body_ctl2_0');
		$this->pause(500);
		$this->verifyNotVisible('ctl0_body_View1');		// view 1
		$this->verifyNotVisible('ctl0_body_View2');		// view 2
		$this->verifyVisible('ctl0_body_ctl2');			// view 3

		// submit: check if the visibility is kept
		$this->clickAndWait("//input[@type='submit' and @value='Submit']", "");
		$this->verifyNotVisible('ctl0_body_View1');		// view 1
		$this->verifyNotVisible('ctl0_body_View2');		// view 2
		$this->verifyVisible('ctl0_body_ctl2');			// view 3
	}
}

?>