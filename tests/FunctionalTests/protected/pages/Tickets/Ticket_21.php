<?php

class Ticket_21 extends TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		
		if(!$this->IsPostBack)
			$this->setViewState("clicks", 0);
	}

	public function doClick($sender, $param)
	{
		$clicks = $this->getViewState("clicks");
		$clicks++;
		$this->label1->setText("Radio button clicks: $clicks");
		$this->setViewState("clicks", $clicks);
	}
}

class Ticket_21_TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open(Prado::getApplication()->getTestPage(__FILE__));
		$this->assertTitle("Verifying Ticket 21");
		$this->clickAndWait("ctl0_Content_button1");
		$this->verifyTextPresent("Radio button clicks: 1", "");
		$this->click("ctl0_Content_button1");
		$this->verifyTextPresent("Radio button clicks: 1", "");

	}
}

?>

