<?php
require_once dirname(__FILE__).'/../../phpunit.php';

if(!defined('PHPUnit_MAIN_METHOD')) {
	define('PHPUnit_MAIN_METHOD', 'Data_SqlMap_AllTests::main');
}

require_once 'DynamicParameterTest.php';
require_once 'DataMapper/AllTests.php';

class Data_SqlMap_AllTests {

	public static function main() {
		PHPUnit_TextUI_TestRunner::run(self::suite());
	}

	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('System.Data.SqlMap');

		$suite->addTestSuite('DynamicParameterTest');
		$suite -> addTest( Data_SqlMap_DataMapper_AllTests::suite() );

		return $suite;
	}
}

if(PHPUnit_MAIN_METHOD == 'Data_SqlMap_AllTests::main') {
	Data_SqlMap_AllTests::main();
}
?>
