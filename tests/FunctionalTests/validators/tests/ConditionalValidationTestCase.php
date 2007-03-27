<?php

class ConditionalValidationTestCase extends SeleniumTestCase
{
	function test()
	{
		$base = "ctl0_Content_";
		$this->open("validators/index.php?page=ConditionalValidation", "");
		$this->verifyTextPresent("Conditional Validation (clientside + server side)", "");
		$this->assertNotVisible("{$base}validator1", "");
		$this->assertNotVisible("{$base}validator2", "");

		$this->click("{$base}submit1");
		$this->assertVisible("{$base}validator1", "");
		$this->assertNotVisible("{$base}validator2", "");

		$this->click("{$base}check1");
		$this->click("{$base}submit1");
		$this->assertVisible("{$base}validator1", "");
		$this->assertVisible("{$base}validator2", "");

		$this->click("{$base}check1");
		$this->click("{$base}submit1");
		$this->assertVisible("{$base}validator1", "");
		$this->assertNotVisible("{$base}validator2", "");

		$this->type("{$base}text1", "testing");
		$this->clickAndWait("{$base}submit1");
		$this->assertNotVisible("{$base}validator1", "");
		$this->assertNotVisible("{$base}validator2", "");

		$this->type("{$base}text1" ,"");
		$this->click("{$base}check1");
		$this->click("{$base}submit1");
		$this->assertVisible("{$base}validator1", "");
		$this->assertVisible("{$base}validator2", "");

		$this->type("{$base}text1", "test");
		$this->type("{$base}text2", "123");
		$this->clickAndWait("{$base}submit1");
		$this->assertNotVisible("{$base}validator1", "");
		$this->assertNotVisible("{$base}validator2", "");

		$this->click("{$base}check1");
		$this->type("{$base}text1", "");
		$this->type("{$base}text2", "");
		$this->click("{$base}submit1");
		$this->assertVisible("{$base}validator1", "");
		$this->assertNotVisible("{$base}validator2", "");

	}

}

?>
