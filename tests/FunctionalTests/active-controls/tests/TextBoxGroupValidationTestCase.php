<?php

class TextBoxGroupValidationTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("active-controls/index.php?page=TextBoxValidationCallback");
		$this->assertTextPresent('TextBox AutoPostBack With Group Validation');
		$this->assertNotVisible('validator1');

		$this->type('ZipCode', 'test');
		$this->assertVisible('validator1');

		$this->type('Address', 'Sydney');
		$this->type('ZipCode', '2000');

		$this->assertNotVisible('validator1');

		$this->pause(800);
		$this->assertValue('City', 'City: Sydney Zip: 2000');
	}
}

?>