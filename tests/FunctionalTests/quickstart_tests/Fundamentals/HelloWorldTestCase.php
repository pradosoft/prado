<?php

//New Test
class HelloWorldTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("/prado3/demos/quickstart/index.php?page=Fundamentals.Samples.HelloWorld.Home", "");
		$this->verifyTitle("Hello World", "");
		$this->clickAndWait("//input[@type='submit' and @value='Click Me']", "");
		$this->clickAndWait("//input[@type='submit' and @value='Hello World']", "");
		$this->verifyTitle("Hello World", "");
	}
}

?>