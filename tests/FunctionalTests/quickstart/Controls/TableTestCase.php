<?php

class QuickstartTableTestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TTable.Home&amp;notheme=true&amp;lang=en");

		$this->assertElementPresent("//table[@rules='all' and @border='1']");
		$this->assertElementPresent("//table/caption[contains(@style,'caption-side:bottom') and text()='This is table caption']");
		$this->assertElementPresent("//th[text()='header cell 2']");
		$this->assertElementPresent("//tr[contains(@style,'text-align:right')]/td[text()='text']");
		$this->assertElementPresent("//td[contains(@style,'text-align:center') and contains(text(),'cell 5')]");

		$this->assertElementPresent("//th[text()='Header 1']");
		$this->assertElementPresent("//td[text()='Cell 1']");
	}
}
