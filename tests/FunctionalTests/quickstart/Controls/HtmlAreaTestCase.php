<?php

class HtmlAreaTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.THtmlArea.Home&amp;notheme=true", "");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		// can't perform any test
	}
}

?>