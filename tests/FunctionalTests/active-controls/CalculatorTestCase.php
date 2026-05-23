<?php

class CalculatorTestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("active-controls/index.php?page=Calculator");
		$this->assertSourceContains("Callback Enabled Calculator");
		$this->assertNotVisible("{$base}summary");

		$this->byId("{$base}sum")->click();
		$this->assertVisible("{$base}summary");

		$this->type("{$base}a", "2");
		$this->type("{$base}b", "5");

		$this->byId("{$base}sum")->click();
		$this->pause(500);

		$this->assertNotVisible("{$base}summary");
		$this->assertValue("{$base}c", "7");
	}
}
