<?php
require_once dirname(__FILE__).'/../phpunit.php';

if(!defined('PHPUnit_MAIN_METHOD')) {
  define('PHPUnit_MAIN_METHOD', 'Xml_AllTests::main');
}

require_once 'TXmlDocumentTest.php';
require_once 'TXmlElementTest.php';
require_once 'TXmlElementListTest.php';

class Xml_AllTests {
  public static function main() {
    PHPUnit_TextUI_TestRunner::run(self::suite());
  }
  
  public static function suite() {
    $suite = new PHPUnit_Framework_TestSuite('System.Xml');
    
    $suite->addTestSuite('TXmlDocumentTest');
	$suite->addTestSuite('TXmlElementTest');
	$suite->addTestSuite('TXmlElementListTest');
    
    return $suite;
  }
}

if(PHPUnit_MAIN_METHOD == 'Xml_AllTests::main') {
  Xml_AllTests::main();
}
?>
