<?php
require_once dirname(__FILE__).'/phpunit.php';

if(!defined('PHPUnit_MAIN_METHOD')) {
  define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}

require_once 'Xml/AllTests.php';
require_once 'IO/AllTests.php';
require_once 'Collections/AllTests.php';
require_once 'I18N/core/AllTests.php';
require_once 'Web/AllTests.php';
require_once 'Web/UI/WebControls/AllTests.php';
require_once 'Web/UI/ActiveControls/AllTests.php';
require_once 'Security/AllTests.php';
require_once 'Caching/AllTests.php';
require_once 'Util/AllTests.php';

require_once 'TComponentTest.php';

class AllTests {
  public static function main() {
    PHPUnit_TextUI_TestRunner::run(self::suite());
  }
  
  public static function suite() {
    $suite = new PHPUnit_Framework_TestSuite('PRADO PHP Framework');
    
	$suite->addTest(Xml_AllTests::suite());
	$suite->addTest(IO_AllTests::suite());
	$suite->addTest(Collections_AllTests::suite());
	$suite->addTest(I18N_core_AllTests::suite());
	$suite->addTest(Web_AllTests::suite());
	$suite->addTest(Web_UI_WebControls_AllTests::suite());
	$suite->addTest(Web_UI_ActiveControls_AllTests::suite());
	$suite->addTest(Security_AllTests::suite());
	$suite->addTest(Caching_AllTests::suite());
	$suite->addTest(Util_AllTests::suite());
	
	$suite->addTestSuite('TComponentTest');

    return $suite;
  }
}

if(PHPUnit_MAIN_METHOD == 'AllTests::main') {
  AllTests::main();
}
?>
