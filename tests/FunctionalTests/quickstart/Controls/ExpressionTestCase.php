<?php

class ExpressionTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TExpression.Home&amp;notheme=true&amp;lang=en", "");

		$this->verifyTitle("PRADO QuickStart Sample", "");

		$this->verifyTextPresent('PRADO QuickStart Sample');
	}
}

?>