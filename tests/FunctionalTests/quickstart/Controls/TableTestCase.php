<?php

class QuickstartTableTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TTable.Home&amp;notheme=true&amp;lang=en");

		$this->assertElementPresent("//table[@rules='all' and @border='1']");
		$this->assertElementPresent("//table/caption[@align='bottom' and text()='This is table caption']");
		$this->assertElementPresent("//th[text()='header cell 2']");
		$this->assertElementPresent("//tr[@align='right']/td[text()='text']");
		$this->assertElementPresent("//td[@align='center' and contains(text(),'cell 5')]");

		$this->assertElementPresent("//th[text()='Header 1']");
		$this->assertElementPresent("//td[text()='Cell 1']");
	}
}
