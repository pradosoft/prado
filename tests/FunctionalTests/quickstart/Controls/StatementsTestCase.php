<?php

class QuickstartStatementsTestCase extends PradoDemosSelenium2Test
{
	function test ()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TStatements.Home&amp;notheme=true&amp;lang=en");

		$this->assertEquals("PRADO QuickStart Sample", $this->title());

		$this->assertSourceContains('UniqueID is \'ctl0$body$ctl0\'');
	}
}
