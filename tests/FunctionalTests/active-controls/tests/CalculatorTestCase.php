<?php

class CalculatorTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=Calculator");
		$this->assertTextPresent("Callback Enabled Calculator");
		$this->assertNotVisible("summary");
		
		$this->click("sum");
		$this->assertVisible("summary");
		
		$this->type("a", "2");
		$this->type("b", "5");
		
		$this->click("sum");
		$this->assertNotVisible("summary");
		
		$this->assertValue("c", "7");
	}
}

?>