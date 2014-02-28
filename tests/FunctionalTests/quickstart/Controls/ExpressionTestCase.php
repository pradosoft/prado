<?php

class QuickstartExpressionTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TExpression.Home&amp;notheme=true&amp;lang=en");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		$this->assertTextPresent('PRADO QuickStart Sample');
	}
}
