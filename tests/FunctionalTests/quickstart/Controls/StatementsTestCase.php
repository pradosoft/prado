<?php

class QuickstartStatementsTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TStatements.Home&amp;notheme=true&amp;lang=en");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		$this->assertTextPresent('UniqueID is \'ctl0$body$ctl0\'');
	}
}
