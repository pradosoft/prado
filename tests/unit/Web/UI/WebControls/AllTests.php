<?php
require_once dirname(__FILE__).'/../../../phpunit.php';

if(!defined('PHPUnit_MAIN_METHOD')) {
  define('PHPUnit_MAIN_METHOD', 'Web_UI_WebControls_AllTests::main');
}

require_once 'TDropDownListTest.php';
require_once 'TLabelTest.php';
require_once 'TRequiredFieldValidatorTest.php';
require_once 'TXmlTransformTest.php';

class Web_UI_WebControls_AllTests {
  public static function main() {
    PHPUnit_TextUI_TestRunner::run(self::suite());
  }
  
  public static function suite() {
    $suite = new PHPUnit_Framework_TestSuite('System.Web.UI.WebControls');
    
	$suite->addTestSuite('TDropDownListTest');
	$suite->addTestSuite('TLabelTest');
	$suite->addTestSuite('TRequiredFieldValidatorTest');
    $suite->addTestSuite('TXmlTransformTest');
	
    return $suite;
  }
}

if(PHPUnit_MAIN_METHOD == 'Web_UI_WebControls_AllTests::main') {
  Web_UI_WebControls_AllTests::main();
}
?>
