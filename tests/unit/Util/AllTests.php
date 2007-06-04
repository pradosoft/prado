<?php
require_once dirname(__FILE__).'/../phpunit.php';

if(!defined('PHPUnit_MAIN_METHOD')) {
  define('PHPUnit_MAIN_METHOD', 'Util_AllTests::main');
}

require_once 'TDateTimeStampTest.php';
require_once 'TLoggerTest.php';

class Util_AllTests {
  public static function main() {
    PHPUnit_TextUI_TestRunner::run(self::suite());
  }
  
  public static function suite() {
    $suite = new PHPUnit_Framework_TestSuite('System.Util');
    
	$suite->addTestSuite('TDateTimeStampTest');
	$suite->addTestSuite('TLoggerTest');
	
    return $suite;
  }
}

if(PHPUnit_MAIN_METHOD == 'Util_AllTests::main') {
  Util_AllTests::main();
}
?>
