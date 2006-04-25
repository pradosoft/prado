<?php

class RequiredListTestCase extends SeleniumTestCase 
{
	
	function test()
	{
		$this->open("validators/index.php?page=RequiredListValidator");
		$this->assertLocation("index.php?page=RequiredListValidator");
		$this->click("submit1");
		$this->assertVisible("validator1");
		$this->assertVisible("validator2");
		$this->assertVisible("validator3");
		$this->click("list1:0");
		$this->select("list2", "label=One");
		$this->select("list2", "label=Two");
		$this->click("list3:3");
		$this->clickAndWait("submit1");
		$this->assertNotVisible("validator1");
		$this->assertNotVisible("validator2");
		$this->assertNotVisible("validator3");
		$this->click("list1:1"); 	 
		$this->click("list1:2"); 	 
		$this->click("list1:3"); 	 
		$this->select("list2", "label=Two");
		$this->click("list1:3"); 	 
		$this->click("submit1"); 		
		$this->assertNotVisible("validator1");
		$this->assertNotVisible("validator2");		
		$this->assertNotVisible("validator3");
		$this->click("list3:3");
		$this->click("submit1"); 		
		$this->pause(200);
		$this->assertNotVisible("validator1");
		$this->assertNotVisible("validator2");		
		$this->assertVisible("validator3");
	}
}

?>