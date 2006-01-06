<?php

//NOTE: This page require UTF-8 aware editors

Prado::using('System.I18N.core.NumberFormatInfo');

class testNumberFormatInfo extends UnitTestCase
{
	function testNumberFormatInfo()
	{
		$this->UnitTestCase();
	}

	function testCurrencyPatterns()
	{
		$numberInfo = NumberFormatInfo::getCurrencyInstance();

		//there should be 2 decimal places.
		$this->assertEqual($numberInfo->DecimalDigits,2);

		$this->assertEqual($numberInfo->DecimalSeparator,'.');

		$this->assertEqual($numberInfo->GroupSeparator,',');

		//there should be only 1 grouping of size 3
		$groupsize = array(3,false);
		$this->assertEqual($numberInfo->GroupSizes, $groupsize);

		//the default negative pattern prefix and postfix
		$negPattern = array('-¤','');
		$this->assertEqual($numberInfo->NegativePattern, $negPattern);

		//the default positive pattern prefix and postfix
		$negPattern = array('¤','');
		$this->assertEqual($numberInfo->PositivePattern, $negPattern);

		//the default currency symbol
		$this->assertEqual($numberInfo->CurrencySymbol, 'US$');

		$this->assertEqual($numberInfo->getCurrencySymbol('JPY'), '¥');

		$this->assertEqual($numberInfo->NegativeInfinitySymbol, '-∞');

		$this->assertEqual($numberInfo->PositiveInfinitySymbol, '+∞');

		$this->assertEqual($numberInfo->NegativeSign, '-');

		$this->assertEqual($numberInfo->PositiveSign, '+');

		$this->assertEqual($numberInfo->NaNSymbol, '�');

		$this->assertEqual($numberInfo->PercentSymbol, '%');

		$this->assertEqual($numberInfo->PerMilleSymbol, '‰');

	}

	function testPatternsSet()
	{
		$numberInfo = NumberFormatInfo::getInstance();

		$numberInfo->DecimalDigits = 0;
		$this->assertEqual($numberInfo->DecimalDigits,0);

		$numberInfo->DecimalSeparator = ',';
		$this->assertEqual($numberInfo->DecimalSeparator,',');

		$numberInfo->GroupSeparator = ' ';
		$this->assertEqual($numberInfo->GroupSeparator,' ');

		$numberInfo->GroupSizes = array(2,3);
		$groupsize = array(2,3);
		$this->assertEqual($numberInfo->GroupSizes, $groupsize);

		$numberInfo->NegativePattern = array('-$$','.');
		$negPattern = array('-$$','.');
		$this->assertEqual($numberInfo->NegativePattern, $negPattern);

		$numberInfo->PositivePattern = array('YY','.');
		$negPattern = array('YY','.');
		$this->assertEqual($numberInfo->PositivePattern, $negPattern);

		//the default CurrencySymbol symbol
		$numberInfo->CurrencySymbol = '$$$';
		$this->assertEqual($numberInfo->CurrencySymbol, '$$$');
	}

	function testLocalizedPatterns()
	{
		$fr = NumberFormatInfo::getInstance('fr');
		$de = NumberFormatInfo::getInstance('de');
		$en = NumberFormatInfo::getInstance('en_US');

		$this->assertEqual($fr->DecimalSeparator, ',');
		$this->assertEqual($de->DecimalSeparator, ',');
		$this->assertEqual($en->DecimalSeparator, '.');

		$this->assertEqual($fr->GroupSeparator, ' ');
		$this->assertEqual($de->GroupSeparator, '.');
		$this->assertEqual($en->GroupSeparator, ',');
	}

}

?>