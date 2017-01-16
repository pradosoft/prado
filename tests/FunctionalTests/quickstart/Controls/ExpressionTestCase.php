<?php

class QuickstartExpressionTestCase extends PradoDemosSelenium2Test
{
	function test ()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TExpression.Home&amp;notheme=true&amp;lang=en");

		$this->assertEquals("PRADO QuickStart Sample", $this->title());

		$this->assertSourceContains('PRADO QuickStart Sample');
	}
}
