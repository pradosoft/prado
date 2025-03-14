<?php

class TextBoxGroupValidationTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = "ctl0_Content_";
		$this->url("active-controls/index.php?page=TextBoxValidationCallback");
		$this->assertSourceContains('TextBox AutoPostBack With Group Validation');
		$this->assertNotVisible("{$base}validator1");

		$this->type("{$base}ZipCode", 'test');
		$this->assertVisible("{$base}validator1");

		$this->type("{$base}Address", 'Sydney');
		$this->type("{$base}ZipCode", '2000');

		$this->assertNotVisible("{$base}validator1");

		$this->assertValue("{$base}City", 'City: Sydney Zip: 2000');
	}
}
