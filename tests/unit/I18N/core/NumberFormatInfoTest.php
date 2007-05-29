<?php
require_once dirname(__FILE__).'/../../phpunit.php';

//NOTE: This page require UTF-8 aware editors
Prado::using('System.I18N.core.NumberFormatInfo');

/**
 * @package System.I18N.core
 */
class NumberFormatInfoTest extends PHPUnit_Framework_TestCase {
  
  function testCurrencyPatterns() {
    $numberInfo = NumberFormatInfo::getCurrencyInstance();
    
    //there should be 2 decimal places.
    $this->assertEquals($numberInfo->DecimalDigits,2);
    $this->assertEquals($numberInfo->DecimalSeparator,'.');
    $this->assertEquals($numberInfo->GroupSeparator,',');
    
    //there should be only 1 grouping of size 3
    $groupsize = array(3,false);
    $this->assertEquals($numberInfo->GroupSizes, $groupsize);
    
    //the default negative pattern prefix and postfix
    $negPattern = array('-¤','');
    $this->assertEquals($numberInfo->NegativePattern, $negPattern);
    
    //the default positive pattern prefix and postfix
    $negPattern = array('¤','');
    $this->assertEquals($numberInfo->PositivePattern, $negPattern);
    
    //the default currency symbol
    $this->assertEquals($numberInfo->CurrencySymbol, 'US$');
    $this->assertEquals($numberInfo->getCurrencySymbol('JPY'), '¥');
    $this->assertEquals($numberInfo->NegativeInfinitySymbol, '-∞');
    $this->assertEquals($numberInfo->PositiveInfinitySymbol, '+∞');
    $this->assertEquals($numberInfo->NegativeSign, '-');
    $this->assertEquals($numberInfo->PositiveSign, '+');
    $this->assertEquals($numberInfo->NaNSymbol, '�');
    $this->assertEquals($numberInfo->PercentSymbol, '%');
    $this->assertEquals($numberInfo->PerMilleSymbol, '‰');  
  }

  function testPatternsSet() {
    $numberInfo = NumberFormatInfo::getInstance();
    
    $numberInfo->DecimalDigits = 0;
    $this->assertEquals($numberInfo->DecimalDigits,0);
    
    $numberInfo->DecimalSeparator = ',';
    $this->assertEquals($numberInfo->DecimalSeparator,',');
    
    $numberInfo->GroupSeparator = ' ';
    $this->assertEquals($numberInfo->GroupSeparator,' ');
    
    $numberInfo->GroupSizes = array(2,3);
    $groupsize = array(2,3);
    $this->assertEquals($numberInfo->GroupSizes, $groupsize);
    
    $numberInfo->NegativePattern = array('-$$','.');
    $negPattern = array('-$$','.');
    $this->assertEquals($numberInfo->NegativePattern, $negPattern);
    
    $numberInfo->PositivePattern = array('YY','.');
    $negPattern = array('YY','.');
    $this->assertEquals($numberInfo->PositivePattern, $negPattern);
    
    //the default CurrencySymbol symbol
    $numberInfo->CurrencySymbol = '$$$';
    $this->assertEquals($numberInfo->CurrencySymbol, '$$$');
  }
  
  function testLocalizedPatterns() {
    $fr = NumberFormatInfo::getInstance('fr');
    $de = NumberFormatInfo::getInstance('de');
    $en = NumberFormatInfo::getInstance('en_US');
    
    $this->assertEquals($fr->DecimalSeparator, ',');
    $this->assertEquals($de->DecimalSeparator, ',');
    $this->assertEquals($en->DecimalSeparator, '.');
    
    $this->assertEquals($fr->GroupSeparator, ' ');
    $this->assertEquals($de->GroupSeparator, '.');
    $this->assertEquals($en->GroupSeparator, ',');
  }
}

?>