<?php

class HelloWorldTestCase extends SeleniumTestCase
{
	function testButton()
	{
		$this->open('../../demos/quickstart/index.php?page=Fundamentals.Samples.HelloWorld.Home');
		$this->verifyTitle('Hello World');
	}
}

?>