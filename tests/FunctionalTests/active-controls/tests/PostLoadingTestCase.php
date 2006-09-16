<?php

class PostLoadingTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('active-controls/index.php?page=PostLoadingTest');
		$this->assertTextPresent('PostLoading Test');

		$this->assertTextNotPresent('Hello World');

		$this->click('div1');
		$this->pause(800);
		$this->type('MyTextBox', 'Hello World');
		$this->click('MyButton');

		$this->pause(800);
		$this->assertTextPresent('Result is Hello World');
	}
}

?>