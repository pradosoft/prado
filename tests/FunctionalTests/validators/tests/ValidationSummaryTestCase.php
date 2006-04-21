<?php

//New Test
class ValidationSummaryTestCase extends SeleniumTestCase
{
	function test()
	{
		$base = "ctl0_Content_";
		
		$this->open("validators/index.php?page=ValidationSummary", "");
		$this->verifyTextPresent("Validation Summary Test", "");
		//$this->verifyText("{$base}summary1", "");
		//$this->verifyText("{$base}summary2", "");
		
		$this->click("//input[@type='submit' and @value='Create New Account']", "");
		$this->assertVisible("{$base}summary1");
		$this->assertNotVisible("{$base}summary2");

		$this->click("//input[@type='submit' and @value='Sign In']", "");
		$this->assertNotVisible("{$base}summary1");
		$this->assertVisible("{$base}summary2");		
		
		$this->type("{$base}Username", "qwe");
		$this->type("{$base}Password", "ewwq");
		$this->click("//input[@type='submit' and @value='Sign In']", "");
		$this->assertNotVisible("{$base}summary1");
		$this->assertVisible("{$base}summary2");		
	
		/*$this->clickAndWait("//input[@type='submit' and @value='Create New Account']", "");	
		$this->type("{$base}UserID", "123");
		$this->type("{$base}Pass", "123");
		$this->clickAndWait("//input[@type='submit' and @value='Sign In']", "");
		//$this->verifyText("{$base}summary1", "");
		//$this->verifyText("{$base}summary2", "");
		$this->clickAndWait("//input[@type='submit' and @value='Create New Account']", "");
		//$this->verifyText("{$base}summary1", "");
		//$this->verifyText("{$base}summary2", "");

		$this->type("{$base}Password", "");
		$this->click("//input[@type='submit' and @value='Create New Account']", "");
		$this->assertVisible("{$base}summary1");
		$this->assertNotVisible("{$base}summary2");
		
		$this->type("{$base}Password", "12312");
		$this->assertVisible("{$base}summary1");
		*/
	}
}

?>