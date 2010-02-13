<?php
require_once dirname(__FILE__).'/../../../phpunit.php';

if(!defined('PHPUnit_MAIN_METHOD')) {
	define('PHPUnit_MAIN_METHOD', 'Testing_Data_Analysis_AllTests::main');
}

require_once 'TDbStatementAnalysisParameterTest.php';
require_once 'TDbStatementAnalysisTest.php';
require_once 'TSimpleDbStatementAnalysisTest.php';

class Data_Analysis_AllTests {

	public static function main() {
		PHPUnit_TextUI_TestRunner::run(self::suite());
	}

	public static function suite() {
		$suite = new PHPUnit_Framework_TestSuite('System.Testing.Data.Analysis');

		$suite->addTestSuite('TDbStatementAnalysisParameterTest');
		$suite->addTestSuite('TDbStatementAnalysisTest');
		$suite->addTestSuite('TSimpleDbStatementAnalysisTest');

		return $suite;
	}
}

if(PHPUnit_MAIN_METHOD == 'Testing_Data_Analysis_AllTests::main') {
	Testing_Data_Analysis_AllTests::main();
}
?>
