<?php

class PostLoadingTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base="ctl0_Content_";
		$this->url('active-controls/index.php?page=PostLoadingTest');
		$this->assertSourceContains('PostLoading Test');

		$this->assertSourceNotContains('Hello World');

		$this->byId('div1')->click();
		$this->pause(800);
		$this->type("{$base}MyTextBox", 'Hello World');
		// workaround for "stale element reference: element is not attached to the page document"
		$this->byId("{$base}MyButton");
		$this->byId("{$base}MyButton")->click();

		$this->pause(800);
		$this->assertSourceContains('Result is Hello World');
	}
}
