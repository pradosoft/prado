<?php

class ActiveControlExpressionTagTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url('active-controls/index.php?page=ActiveControlExpressionTag');
		$this->assertContains('Active Control With Expression Tag Test', $this->source());
		$this->assertNotContains('Text box content:', $this->source());

		$this->type("{$base}textbox1", 'Hello world');
		$this->byId("{$base}button1")->click();
		$this->pause(800);

		$this->assertText("repeats", 'result - 1 result - two');
		$this->assertText("contents", 'Text box content: Hello world');
	}
}
