<?php

class CalculatorTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=Calculator");
		$this->assertTextPresent("Callback Enabled Calculator");
		$this->assertNotVisible("{$base}summary");

		$this->click("{$base}sum");
		$this->assertVisible("{$base}summary");

		$this->type("{$base}a", "2");
		$this->type("{$base}b", "5");

		$this->click("{$base}sum");
		$this->pause(500);

		$this->assertNotVisible("{$base}summary");
		$this->assertValue("{$base}c", "7");
	}
}
