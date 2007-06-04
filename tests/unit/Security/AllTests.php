<?php
require_once dirname(__FILE__).'/../phpunit.php';

if(!defined('PHPUnit_MAIN_METHOD')) {
  define('PHPUnit_MAIN_METHOD', 'Security_AllTests::main');
}

require_once 'TAuthManagerTest.php';
require_once 'TAuthorizationRuleTest.php';
require_once 'TSecurityManagerTest.php';
require_once 'TUserManagerTest.php';
require_once 'TUserTest.php';

class Security_AllTests {
  public static function main() {
    PHPUnit_TextUI_TestRunner::run(self::suite());
  }
  
  public static function suite() {
    $suite = new PHPUnit_Framework_TestSuite('System.Security');
    
	$suite->addTestSuite('TAuthManagerTest');
	$suite->addTestSuite('TAuthorizationRuleTest');
	$suite->addTestSuite('TSecurityManagerTest');
	$suite->addTestSuite('TUserManagerTest');
	$suite->addTestSuite('TUserTest');
	
    return $suite;
  }
}

if(PHPUnit_MAIN_METHOD == 'Security_AllTests::main') {
  Security_AllTests::main();
}
?>
