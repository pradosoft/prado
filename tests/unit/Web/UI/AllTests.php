<?php
require_once dirname(__FILE__).'/../../phpunit.php';

if(!defined('PHPUnit_MAIN_METHOD')) {
  define('PHPUnit_MAIN_METHOD', 'Web_UI_AllTests::main');
}

require_once 'TClientScriptManagerTest.php';
require_once 'TControlAdapterTest.php';
require_once 'TControlTest.php';
require_once 'TFormTest.php';
require_once 'TPageTest.php';
/*require_once 'TTemplateControlTest.php';
require_once 'TTemplateManagerTest.php';
require_once 'TThemeManagerTest.php';
require_once 'THtmlWriterTest.php';
require_once 'TPageStatePersisterTest.php';
require_once 'TSessionPageStatePersisterTest.php';*/

class Web_UI_AllTests {
  public static function main() {
    PHPUnit_TextUI_TestRunner::run(self::suite());
  }
  
  public static function suite() {
    $suite = new PHPUnit_Framework_TestSuite('System.Web.UI');
    
	$suite->addTestSuite('TClientScriptManagerTest');
	$suite->addTestSuite('TControlAdapterTest');
    $suite->addTestSuite('TControlTest');
	$suite->addTestSuite('TFormTest');
	$suite->addTestSuite('TPageTest');
	/*$suite->addTestSuite('TTemplateControlTest');
	$suite->addTestSuite('TTemplateManagerTest');
	$suite->addTestSuite('TThemeManagerTest');
	$suite->addTestSuite('THtmlWriterTest');
	$suite->addTestSuite('TPageStatePersisterTest');
	$suite->addTestSuite('TSessionPageStatePersisterTest');*/
	
    return $suite;
  }
}

if(PHPUnit_MAIN_METHOD == 'Web_UI_AllTests::main') {
  Web_UI_AllTests::main();
}
?>
