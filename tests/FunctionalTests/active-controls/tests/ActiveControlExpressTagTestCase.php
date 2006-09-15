<?php

class ActiveControlExpressionTagTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('active-controls/index.php?page=ActiveControlExpressionTag');
		$this->assertTextPresent('Active Control With Expression Tag Test');
		$this->assertTextNotPresent('Text box content:');

		$this->type('textbox1', 'Hello world');
		$this->click('button1');
		$this->pause(800);

		$this->assertText('repeats', 'result - 1 result - two');
		$this->assertText('contents', 'Text box content: Hello world');
	}
}

?>