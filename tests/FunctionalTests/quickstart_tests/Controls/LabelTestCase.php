<?php

//New Test
class LabelTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("/prado3/demos/quickstart/?page=Controls.Samples.TLabel.Home", "");
		$this->verifyTitle("PRADO QuickStart Sample", "");
		$this->verifyTextPresent("This is a label with customized color and font.", "");
		$this->verifyTextPresent("This is a form label associated with the TTextBox control below", "");
		$this->verifyTextPresent("This is a label with empty Text property and nonempty body", "");
		$this->verifyTextPresent("This is a disabled label", "");
//		$this->verifyElementPresent("//span[@style='color:yellow;background-color:blue;font-size:14pt;font-family:Arial;']");
		$this->verifyElementPresent("//span[@disabled='disabled']");
		$this->verifyElementPresent("//label[@for=\"ctl0_body_test\"]");
		$this->type("ctl0\$body\$test", "test");
	}
}

?>