<?php

class ActiveControlExpressionTagTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url('active-controls/index.php?page=ActiveControlExpressionTag');
		$this->assertTextPresent('Active Control With Expression Tag Test');
		$this->assertTextNotPresent('Text box content:');

		$this->type("{$base}textbox1", 'Hello world');
		$this->click("{$base}button1");
		$this->pause(800);

		$this->assertText("repeats", 'result - 1 result - two');
		$this->assertText("contents", 'Text box content: Hello world');
	}
}
