<?php

class testTButton extends TPage
{
	function onLoad($param)
	{
		$this->button2;
	}
}


class testTButtonCase extends SeleniumTestCase
{
	function setup()
	{
		$this->initPage(__FILE__);
		$this->open($this->Page->Request->TestUrl);
	}

	function testButtonClick()
	{
		$this->assertTextPresent("TButton Functional Test");
		$this->click($this->Page->button1);
	}
}

?>