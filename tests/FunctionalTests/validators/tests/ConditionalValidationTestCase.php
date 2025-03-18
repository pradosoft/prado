<?php

class ConditionalValidationTestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url("validators/index.php?page=ConditionalValidation");
		$this->assertSourceContains("Conditional Validation (clientside + server side)");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");

		$this->byId("{$base}submit1")->click();
		$this->assertVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");

		$this->byId("{$base}check1")->click();
		$this->byId("{$base}submit1")->click();
		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");

		$this->byId("{$base}check1")->click();
		$this->byId("{$base}submit1")->click();
		$this->pause(50);
		$this->assertVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");

		$this->type("{$base}text1", "testing");
		$this->byId("{$base}submit1")->click();
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");

		$this->type("{$base}text1", "");
		$this->byId("{$base}check1")->click();
		$this->byId("{$base}submit1")->click();
		$this->assertVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");

		$this->type("{$base}text1", "test");
		$this->type("{$base}text2", "123");
		$this->byId("{$base}submit1")->click();
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");

		$this->byId("{$base}check1")->click();
		$this->type("{$base}text1", "");
		$this->type("{$base}text2", "");
		$this->byId("{$base}submit1")->click();
		$this->assertVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
	}
}
