<?php

//New Test
class QuickstartLabelTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TLabel.Home&amp;notheme=true&amp;lang=en");
		$this->assertEquals("PRADO QuickStart Sample", $this->title());
		$this->assertContains("This is a label with customized color and font.", $this->source());
		$this->assertContains("This is a form label associated with the TTextBox control below", $this->source());
		$this->assertContains("This is a label with empty Text property and <b>nonempty body</b>", $this->source());
		$this->assertContains("This is a disabled label", $this->source());

		$this->assertAttribute("ctl0_body_Label2@disabled","regexp:true|disabled");

		//$this->assertAttribute("ctl0_body_Label1@for","ctl0_body_test");

		$this->type("ctl0\$body\$test", "test");
	}
}
