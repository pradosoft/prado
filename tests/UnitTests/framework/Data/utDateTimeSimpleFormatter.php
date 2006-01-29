<?php

// NOTE: This file must be saved with charset GB2312
require_once(dirname(__FILE__).'/../common.php');

Prado::using('System.Data.TDateTimeSimpleFormatter');

class utDateTimeSimpleFormatter extends UnitTestCase
{
	function testFormatting()
	{
		$time = mktime(0,0,0,12,30,2005);
		$pattern = "dd-MM-yyyy";
		$expect = '30-12-2005';
		
		$formatter = new TDateTimeSimpleFormatter($pattern);
		$this->assertEqual($expect, $formatter->format($time));

		$time = mktime(0,0,0,5,6,2005);
		$pattern = "d-M-yy";
		$expect = "6-5-05";

		$formatter->setPattern($pattern);
		$this->assertEqual($expect, $formatter->format($time));

		$pattern = "dd-MM-yy";
		$expect = "06-05-05";

		$formatter->setPattern($pattern);
		$this->assertEqual($expect, $formatter->format($time));

		$pattern = "yyyy年MM月dd日";
		$expect = "2005年05月06日";
		
		$formatter = new TDateTimeSimpleFormatter($pattern, 'GB2312');
		$this->assertEqual($expect, $formatter->format($time));

		$pattern = "MM/dd/yyyy";
		$expect = "05/06/2005";
		
		$formatter = new TDateTimeSimpleFormatter($pattern, 'UTF-8');
		$this->assertEqual($expect, $formatter->format($time));

	}

	function testParsing()
	{
		$pattern = "yyyy年MM月dd日";
		$value = "2005年05月06日";
		$expect = mktime(0,0,0,5,6,2005);

		$formatter = new TDateTimeSimpleFormatter($pattern, 'GB2312');
		$this->assertEqual($expect, $formatter->parse($value));

		$pattern = "dd-MM-yy";
		$value= "06-05-05";
		
		$formatter = new TDateTimeSimpleFormatter($pattern);
		$this->assertEqual($expect, $formatter->parse($value));

		$pattern = "d-M-yy";
		$value = "6-5-05";
		$formatter = new TDateTimeSimpleFormatter($pattern);
		$this->assertEqual($expect, $formatter->parse($value));

		$pattern = "MM/dd/yyyy";
		$value = "05/06/2005";
		$formatter = new TDateTimeSimpleFormatter($pattern);
		$this->assertEqual($expect, $formatter->parse($value));


		$pattern = "dd-MM-yyyy";
		$value = '30-12-2005';
		$expect = mktime(0,0,0,12,30,2005);

		$formatter = new TDateTimeSimpleFormatter($pattern);
		$this->assertEqual($expect, $formatter->parse($value));
	}
}

?>