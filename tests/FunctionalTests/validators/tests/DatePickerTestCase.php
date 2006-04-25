<?php

class DatePickerTestCase extends SeleniumTestCase
{
	function test()
	{	
		$base = "ctl0_Content_";
		$this->open("validators/index.php?page=DatePicker", "");
		$this->verifyTextPresent("Date Picker validation Test", "");
		$this->assertNotVisible("{$base}validator1", "");
		$this->assertNotVisible("{$base}validator2", "");
		$this->assertNotVisible("{$base}validator4", "");
		$this->assertNotVisible("{$base}validator5", "");
		$this->assertNotVisible("{$base}validator6", "");
		$this->assertNotVisible("{$base}validator8", "");
		
		$this->click("{$base}submit1");
		$this->assertVisible("{$base}validator1", "");
		$this->assertNotVisible("{$base}validator2", "");
		$this->assertVisible("{$base}validator4", "");
		$this->assertVisible("{$base}validator5", "");
		$this->assertNotVisible("{$base}validator6", "");
		$this->assertVisible("{$base}validator8", "");
		
		$this->click("{$base}submit1");
		$this->type("{$base}picker1", "13/4/2006");
		$this->select("{$base}picker2_month", "label=9");
		$this->select("{$base}picker2_day", "label=10");
		$this->type("{$base}picker3", "14/4/2006");
		$this->type("{$base}picker4", "7/4/2006");
		$this->select("{$base}picker5_day", "label=6");
		$this->select("{$base}picker5_month", "label=3");
		$this->select("{$base}picker5_year", "label=2007");
		$this->select("{$base}picker6_month", "label=3");
		$this->select("{$base}picker6_year", "label=2007");
		$this->select("{$base}picker6_day", "label=5");
		
		$this->click("{$base}submit1");
		
		$this->assertNotVisible("{$base}validator1", "");
		$this->assertVisible("{$base}validator2", "");
		$this->assertNotVisible("{$base}validator4", "");
		$this->assertNotVisible("{$base}validator5", "");
		$this->assertVisible("{$base}validator6", "");
		$this->assertVisible("{$base}validator8", "");		
		
		$this->type("{$base}picker1", "20/4/2007");
		$this->type("{$base}picker4", "29/4/2006");
		$this->select("{$base}picker6_day", "label=10");		
		
		$this->clickAndWait("{$base}submit1");
		
		$this->assertNotVisible("{$base}validator1", "");
		$this->assertNotVisible("{$base}validator2", "");
		$this->assertNotVisible("{$base}validator4", "");
		$this->assertNotVisible("{$base}validator5", "");
		$this->assertNotVisible("{$base}validator6", "");
		$this->assertNotVisible("{$base}validator8", "");		
	}
	
}

?>