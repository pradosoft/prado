<?php
require_once dirname(__FILE__).'/../phpunit.php';

if(!defined('PHPUnit_MAIN_METHOD')) {
	define('PHPUnit_MAIN_METHOD', 'IO_AllTests::main');
}

require_once 'TTextWriterTest.php';

class IO_AllTests {
	public static function main() {
		PHPUnit_TextUI_TestRunner::run(self::suite());
	}
  
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('System.IO');
    
		$suite->addTestSuite('TTextWriterTest');
    
		return $suite;
	}
}

if(PHPUnit_MAIN_METHOD == 'IO_AllTests::main') {
	IO_AllTests::main();
}
?>
