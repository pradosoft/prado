<?php

class DatePickerTestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$year = date('Y');
		$year2 = $year + 1;
		$base = "ctl0_Content_";
		$this->url("validators/index.php?page=DatePicker");
		$this->assertSourceContains("Date Picker validation Test");
		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->assertNotVisible("{$base}validator4");
		$this->assertNotVisible("{$base}validator5");
		$this->assertNotVisible("{$base}validator6");
		$this->assertNotVisible("{$base}validator8");

		$this->byId("{$base}submit1")->click();
		$this->pause(500);
		$this->assertVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");

		//the range validator is visible because the date is a drop down list
		//thus has default value != ""
		$this->assertVisible("{$base}validator4");
		$this->assertVisible("{$base}validator5");
		$this->assertNotVisible("{$base}validator6");
		$this->assertVisible("{$base}validator8");

		$this->type("{$base}picker1", "13/4/$year");
		$this->select("{$base}picker2_month", "9");
		$this->select("{$base}picker2_day", "10");
		$this->select("{$base}picker2_year", "$year");
		$this->pause(250);
		$this->type("{$base}picker3", "14/4/$year");
		$this->pause(250);
		$this->type("{$base}picker4", "7/4/$year");
		$this->select("{$base}picker5_day", "6");
		$this->select("{$base}picker5_month", "3");
		$this->select("{$base}picker5_year", "$year2");
		$this->select("{$base}picker6_month", "3");
		$this->select("{$base}picker6_year", "$year2");
		$this->select("{$base}picker6_day", "5");
		$this->byId("{$base}submit1")->click();
		$this->pause(500);

		$this->assertNotVisible("{$base}validator1");
		$this->assertVisible("{$base}validator2");
		$this->assertNotVisible("{$base}validator4");
		$this->assertNotVisible("{$base}validator5");
		$this->assertVisible("{$base}validator6");
		$this->assertVisible("{$base}validator8");

		$this->type("{$base}picker1", "20/4/$year2");
		$this->type("{$base}picker4", "29/4/$year");
		$this->select("{$base}picker6_day", "10");

		$this->byId("{$base}submit1")->click();

		$this->assertNotVisible("{$base}validator1");
		$this->assertNotVisible("{$base}validator2");
		$this->assertNotVisible("{$base}validator4");
		$this->assertNotVisible("{$base}validator5");
		$this->assertNotVisible("{$base}validator6");
		$this->assertNotVisible("{$base}validator8");
	}
}
