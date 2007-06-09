<?php
require_once dirname(__FILE__).'/../../../phpunit.php';

if(!defined('PHPUnit_MAIN_METHOD')) {
  define('PHPUnit_MAIN_METHOD', 'Web_UI_ActiveControls_AllTests::main');
}

require_once 'TActiveHiddenFieldTest.php';

class Web_UI_ActiveControls_AllTests {
  public static function main() {
    PHPUnit_TextUI_TestRunner::run(self::suite());
  }
  
  public static function suite() {
    $suite = new PHPUnit_Framework_TestSuite('System.Web.UI.ActiveControls');
    
	$suite->addTestSuite('TActiveHiddenFieldTest');	
    return $suite;
  }
}

if(PHPUnit_MAIN_METHOD == 'Web_UI_ActiveControls_AllTests::main') {
  Web_UI_ActiveControls_AllTests::main();
}
?>
