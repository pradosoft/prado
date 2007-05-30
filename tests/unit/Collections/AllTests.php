<?php
require_once dirname(__FILE__).'/../phpunit.php';

if(!defined('PHPUnit_MAIN_METHOD')) {
  define('PHPUnit_MAIN_METHOD', 'Collections_AllTests::main');
}

require_once 'TListTest.php';
require_once 'TMapTest.php';
require_once 'TQueueTest.php';
require_once 'TStackTest.php';

class Collections_AllTests {
  public static function main() {
    PHPUnit_TextUI_TestRunner::run(self::suite());
  }
  
  public static function suite() {
    $suite = new PHPUnit_Framework_TestSuite('System.Collections');
    
    $suite->addTestSuite('TListTest');
	$suite->addTestSuite('TMapTest');
	$suite->addTestSuite('TQueueTest');
	$suite->addTestSuite('TStackTest');
    
    return $suite;
  }
}

if(PHPUnit_MAIN_METHOD == 'Collections_AllTests::main') {
  Collections_AllTests::main();
}
?>
