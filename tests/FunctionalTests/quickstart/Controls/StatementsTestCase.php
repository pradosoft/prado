<?php

class StatementsTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TStatements.Home&amp;notheme=true&amp;lang=en", "");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		$this->verifyTextPresent('UniqueID is \'ctl0$body$ctl0\'');
	}
}

?>