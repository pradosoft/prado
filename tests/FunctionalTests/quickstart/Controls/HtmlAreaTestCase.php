<?php

class QuickstartHtmlAreaTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.THtmlArea.Home&amp;notheme=true&amp;lang=en");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// can't perform any test
	}
}
