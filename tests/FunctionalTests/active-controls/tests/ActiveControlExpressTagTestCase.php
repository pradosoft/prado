<?php

class ActiveControlExpressionTagTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('active-controls/index.php?page=ActiveControlExpressionTag');
		$this->assertSourceContains('Active Control With Expression Tag Test');
		$this->assertSourceNotContains('Text box content:');

		$this->type("{$base}textbox1", 'Hello world');
		$this->byId("{$base}button1")->click();
		$this->pauseFairAmount();

		$this->assertText("repeats", 'result - 1 result - two');
		$this->assertText("contents", 'Text box content: Hello world');
	}
}
