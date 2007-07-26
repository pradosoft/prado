<?php

class TableTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TTable.Home&amp;notheme=true&amp;lang=en", "");

		$this->verifyElementPresent("//table[@rules='all' and @border='1']");
		$this->verifyElementPresent("//table/caption[@align='bottom' and text()='This is table caption']");
		$this->verifyElementPresent("//th[text()='header cell 2']");
		$this->verifyElementPresent("//tr[@align='right']/td[text()='text']");
		$this->verifyElementPresent("//td[@align='center' and contains(text(),'cell 5')]");

		$this->verifyElementPresent("//th[text()='Header 1']");
		$this->verifyElementPresent("//td[text()='Cell 1']");
	}
}

?>