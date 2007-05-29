<?php
require_once dirname(__FILE__).'/../../phpunit.php';

if(!defined('PHPUnit_MAIN_METHOD')) {
  define('PHPUnit_MAIN_METHOD', 'I18N_core_AllTests::main');
}

require_once 'CultureInfoTest.php';
require_once 'DateFormatTest.php';
require_once 'DateTimeFormatInfoTest.php';
require_once 'NumberFormatInfoTest.php';
require_once 'NumberFormatTest.php';

class I18N_core_AllTests {
  public static function main() {
    PHPUnit_TextUI_TestRunner::run(self::suite());
  }
  
  public static function suite() {
    $suite = new PHPUnit_Framework_TestSuite('System.I18N.core');
    
    $suite->addTestSuite('CultureInfoTest');
	$suite->addTestSuite('DateFormatTest');
	$suite->addTestSuite('DateTimeFormatInfoTest');
	$suite->addTestSuite('NumberFormatInfoTest');
	$suite->addTestSuite('NumberFormatTest');
    
    return $suite;
  }
}

if(PHPUnit_MAIN_METHOD == 'I18N_core_AllTests::main') {
  I18N_core_AllTests::main();
}
?>
