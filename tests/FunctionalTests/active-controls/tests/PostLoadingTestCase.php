<?php

class PostLoadingTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base="ctl0_Content_";
		$this->url('active-controls/index.php?page=PostLoadingTest');
		$this->assertTextPresent('PostLoading Test');

		$this->assertTextNotPresent('Hello World');

		$this->click('div1');
		$this->pause(800);
		$this->type("{$base}MyTextBox", 'Hello World');
		$this->click("{$base}MyButton");

		$this->pause(800);
		$this->assertTextPresent('Result is Hello World');
	}
}
