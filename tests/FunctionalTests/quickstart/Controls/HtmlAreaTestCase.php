<?php

class QuickstartHtmlAreaTestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.THtmlArea.Home&amp;notheme=true&amp;lang=en");

		$this->assertTitle("PRADO QuickStart Sample");

		// can't perform any test
	}
}
