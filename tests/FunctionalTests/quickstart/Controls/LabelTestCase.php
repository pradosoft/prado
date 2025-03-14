<?php

//New Test
class QuickstartLabelTestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TLabel.Home&amp;notheme=true&amp;lang=en");
		$this->assertTitle("PRADO QuickStart Sample");
		$this->assertSourceContains("This is a label with customized color and font.");
		$this->assertSourceContains("This is a form label associated with the TTextBox control below");
		$this->assertSourceContains("This is a label with empty Text property and <b>nonempty body</b>");
		$this->assertSourceContains("This is a disabled label");

		$this->assertAttribute("ctl0_body_Label2@disabled", "regexp:true|disabled");

		//$this->assertAttribute("ctl0_body_Label1@for","ctl0_body_test");

		$this->type("ctl0\$body\$test", "test");
	}
}
