<?php
require_once dirname(__FILE__).'/../../phpunit.php';

if(!defined('PHPUnit_MAIN_METHOD')) {
	define('PHPUnit_MAIN_METHOD', 'Data_DataGateway_AllTests::main');
}

require_once 'TSqlCriteriaTest.php';

class Data_DataGateway_AllTests {

	public static function main() {
		PHPUnit_TextUI_TestRunner::run(self::suite());
	}
  
	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('System.Data.DataGateway');
    
		$suite->addTestSuite('TSqlCriteriaTest');
		
		return $suite;
	}
}

if(PHPUnit_MAIN_METHOD == 'Data_DataGateway_AllTests::main') {
	Data_DataGateway_AllTests::main();
}
?>
