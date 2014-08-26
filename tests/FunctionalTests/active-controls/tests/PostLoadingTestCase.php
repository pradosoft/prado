<?php

class PostLoadingTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base="ctl0_Content_";
		$this->url('active-controls/index.php?page=PostLoadingTest');
		$this->assertContains('PostLoading Test', $this->source());

		$this->assertNotContains('Hello World', $this->source());

		$this->byId('div1')->click();
		$this->pause(800);
		$this->type("{$base}MyTextBox", 'Hello World');
		$this->byId("{$base}MyButton")->click();

		$this->pause(800);
		$this->assertContains('Result is Hello World', $this->source());
	}
}
