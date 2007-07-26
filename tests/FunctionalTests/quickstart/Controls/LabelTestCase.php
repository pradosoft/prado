<?php

//New Test
class LabelTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TLabel.Home&amp;notheme=true&amp;lang=en", "");
		$this->verifyTitle("PRADO QuickStart Sample", "");
		$this->verifyTextPresent("This is a label with customized color and font.", "");
		$this->verifyTextPresent("This is a form label associated with the TTextBox control below", "");
		$this->verifyTextPresent("This is a label with empty Text property and nonempty body", "");
		$this->verifyTextPresent("This is a disabled label", "");

		$this->verifyAttribute("ctl0_body_Label2@disabled","regexp:true|disabled");

		//$this->verifyAttribute("ctl0_body_Label1@for","ctl0_body_test");

		$this->type("ctl0\$body\$test", "test");
	}
}

?>