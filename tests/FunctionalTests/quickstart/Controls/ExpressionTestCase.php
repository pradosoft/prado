<?php

class QuickstartExpressionTestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TExpression.Home&amp;notheme=true&amp;lang=en");

		$this->assertTitle("PRADO QuickStart Sample");

		$this->assertSourceContains('PRADO QuickStart Sample');
	}
}
