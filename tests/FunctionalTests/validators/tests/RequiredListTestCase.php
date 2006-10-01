<?php

class RequiredListTestCase extends SeleniumTestCase
{

	function test()
	{
		//problem with test runner clicking on radio buttons
		$this->skipBrowsers(self::OPERA);

		$base = "ctl0_Content_";
		$this->open("validators/index.php?page=RequiredListValidator");
		$this->click("{$base}submit1");
		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
		$this->assertVisible("{$base}validator3");
		$this->click("{$base}list1_c0");
		$this->addSelection("{$base}list2", "label=One");
		$this->addSelection("{$base}list2", "label=Two");
		$this->click("{$base}list3_c3");
		$this->clickAndWait("{$base}submit1");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->assertNotVisible("{$base}validator3");
		$this->click("{$base}list1_c1");
		$this->click("{$base}list1_c2");
		$this->click("{$base}list1_c3");
		$this->addSelection("{$base}list2", "label=Two");
		$this->click("{$base}list1_c3");
		$this->clickAndWait("{$base}submit1");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->assertNotVisible("{$base}validator3");
		$this->click("{$base}list3_c3");
		$this->clickAndWait("{$base}submit1");
		$this->pause(200);
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->assertNotVisible("{$base}validator3");
	}
}

?>