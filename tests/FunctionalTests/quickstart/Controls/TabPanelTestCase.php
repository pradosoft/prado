<?php

class QuickstartTabPanelTestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TTabPanel.Home&amp;notheme=true&amp;lang=en");

		$this->assertTitle("PRADO QuickStart Sample");

		// verify initial visibility
		$this->assertNotVisible('ctl0_body_View1');		// view 1
		$this->assertVisible('ctl0_body_View2');		// view 2
		$this->assertNotVisible('ctl0_body_ctl2');		// view 3

		// switching to the first view
		$this->byId('ctl0_body_View1_0')->click();
		$this->pause(500);
		$this->assertVisible('ctl0_body_View1');		// view 1
		$this->assertNotVisible('ctl0_body_View2');		// view 2
		$this->assertNotVisible('ctl0_body_ctl2');		// view 3
		$this->assertNotVisible('ctl0_body_View11');	// view 11
		$this->assertVisible('ctl0_body_View21');		// view 21

		// switching to View11
		$this->byId('ctl0_body_View11_0')->click();
		$this->pause(500);
		$this->assertVisible('ctl0_body_View1');		// view 1
		$this->assertNotVisible('ctl0_body_View2');		// view 2
		$this->assertNotVisible('ctl0_body_ctl2');		// view 3
		$this->assertVisible('ctl0_body_View11');		// view 11
		$this->assertNotVisible('ctl0_body_View21');	// view 21

		// switching to the third view
		$this->byId('ctl0_body_ctl2_0')->click();
		$this->pause(500);
		$this->assertNotVisible('ctl0_body_View1');		// view 1
		$this->assertNotVisible('ctl0_body_View2');		// view 2
		$this->assertVisible('ctl0_body_ctl2');			// view 3

		// submit: check if the visibility is kept
		$this->byXPath("//input[@type='submit' and @value='Submit']")->click();
		$this->assertNotVisible('ctl0_body_View1');		// view 1
		$this->assertNotVisible('ctl0_body_View2');		// view 2
		$this->assertVisible('ctl0_body_ctl2');			// view 3
	}
}
