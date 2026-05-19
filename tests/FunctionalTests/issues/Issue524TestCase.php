<?php

/**
 * Testcase for Issue 524
 * Encoding issues caused an error when serializing the
 * response
 **/
class Issue524TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('issues/index.php?page=Issue524');
		$this->assertSourceContains('Issue 524 Test');
		$base = 'ctl0_Content_';

		$this->byID("{$base}buttonOk")->click();
		$this->assertText("{$base}Validator", "fünf");
	}
}
