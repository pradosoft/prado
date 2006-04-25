<?php

class RequiredListTestCase extends SeleniumTestCase 
{
	
	function test()
	{
		$base = "ctl0_Content_";
		$this->open("validators/index.php?page=RequiredListValidator");
		$this->assertLocation("index.php?page=RequiredListValidator");
		$this->click("{$base}submit1");
		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
		$this->assertVisible("{$base}validator3");
		$this->click("{$base}list1_0");
		$this->select("{$base}list2", "label=One");
		$this->select("{$base}list2", "label=Two");
		$this->click("{$base}list3_3");
		$this->clickAndWait("{$base}submit1");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->assertNotVisible("{$base}validator3");
		$this->click("{$base}list1_1"); 	 
		$this->click("{$base}list1_2"); 	 
		$this->click("{$base}list1_3"); 	 
		$this->select("{$base}list2", "label=Two");
		$this->click("{$base}list1_3"); 	 
		$this->click("{$base}submit1"); 		
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");		
		$this->assertNotVisible("{$base}validator3");
		$this->click("{$base}list3_3");
		$this->click("{$base}submit1"); 		
		$this->pause(200);
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");		
		$this->assertNotVisible("{$base}validator3");
	}
}

?>