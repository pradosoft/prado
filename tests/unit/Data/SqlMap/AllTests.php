<?php
require_once dirname(__FILE__).'/../../phpunit.php';

if(!defined('PHPUnit_MAIN_METHOD')) {
	define('PHPUnit_MAIN_METHOD', 'Data_SqlMap_AllTests::main');
}

require_once 'DynamicParameterTest.php';

class Data_SqlMap_AllTests {

	public static function main() {
		PHPUnit_TextUI_TestRunner::run(self::suite());
	}

	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('System.Data.SqlMap');

		$suite->addTestSuite('DynamicParameterTest');

		return $suite;
	}
}

if(PHPUnit_MAIN_METHOD == 'Data_SqlMap_AllTests::main') {
	Data_SqlMap_AllTests::main();
}
?>