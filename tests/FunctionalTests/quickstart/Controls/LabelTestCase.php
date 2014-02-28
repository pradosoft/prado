<?php

//New Test
class QuickstartLabelTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TLabel.Home&amp;notheme=true&amp;lang=en");
		$this->verifyTitle("PRADO QuickStart Sample", "");
		$this->assertTextPresent("This is a label with customized color and font.", "");
		$this->assertTextPresent("This is a form label associated with the TTextBox control below", "");
		$this->assertTextPresent("This is a label with empty Text property and <b>nonempty body</b>", "");
		$this->assertTextPresent("This is a disabled label", "");

		$this->verifyAttribute("ctl0_body_Label2@disabled","regexp:true|disabled");

		//$this->verifyAttribute("ctl0_body_Label1@for","ctl0_body_test");

		$this->type("ctl0\$body\$test", "test");
	}
}
