<?php

class MyTestCase extends PradoGenericSeleniumTest
{
	function test1()
	{
		$this->open('http://127.0.0.1');
		$this->assertTextNotPresent('asd');
	}
}
