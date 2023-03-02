<?php

//New Test
class ValidationSummaryTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";

		$this->url("validators/index.php?page=ValidationSummary");
		$this->assertSourceContains("Validation Summary Test");
		//$this->assertText("{$base}summary1", "");
		//$this->assertText("{$base}summary2", "");

		$this->byXPath("//input[@type='submit' and @value='Create New Account']")->click();
		$this->assertVisible("{$base}summary1");
		$this->assertNotVisible("{$base}summary2");

		$this->byXPath("//input[@type='submit' and @value='Sign In']")->click();
		$this->assertNotVisible("{$base}summary1");
		$this->assertVisible("{$base}summary2");

		$this->type("{$base}Username", "qwe");
		$this->type("{$base}Password", "ewwq");
		$this->byXPath("//input[@type='submit' and @value='Sign In']")->click();
		$this->assertNotVisible("{$base}summary1");
		$this->assertVisible("{$base}summary2");

		/*$this->byXPath("//input[@type='submit' and @value='Create New Account']")->click();
		$this->type("{$base}UserID", "123");
		$this->type("{$base}Pass", "123");
		$this->byXPath("//input[@type='submit' and @value='Sign In']")->click();
		//$this->assertText("{$base}summary1", "");
		//$this->assertText("{$base}summary2", "");
		$this->byXPath("//input[@type='submit' and @value='Create New Account']")->click();
		//$this->assertText("{$base}summary1", "");
		//$this->assertText("{$base}summary2", "");

		$this->type("{$base}Password", "");
		$this->byXPath("//input[@type='submit' and @value='Create New Account']")->click();
		$this->assertVisible("{$base}summary1");
		$this->assertNotVisible("{$base}summary2");

		$this->type("{$base}Password", "12312");
		$this->assertVisible("{$base}summary1");
		*/
	}
}
