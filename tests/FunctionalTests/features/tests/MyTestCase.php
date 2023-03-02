<?php

class MyTestCase extends PradoGenericSelenium2Test
{
	public function test1()
	{
		$this->url('http://127.0.0.1');
		$this->assertSourceNotContains('asd');
	}
}
