<?php

class MyTestCase extends SeleniumTestCase
{
	function test1()
	{
		$this->open('http://127.0.0.1');
		$this->assertTextNotPresent('asd');
	}

	function test2()
	{
		$this->skipBrowsers(self::FIREFOX);
		$this->open('http://127.0.0.1');
		$this->assertTextNotPresent('asd');
	}
}

?>