<?php

if(!defined('FRAMEWORK_DIR'))
	define('FRAMEWORK_DIR',realpath(dirname(__FILE__).'/../../../framework'));
if(!defined('SIMPLETEST_DIR'))
	define('SIMPLETEST_DIR',realpath(dirname(__FILE__).'/../simpletest'));

require_once(SIMPLETEST_DIR.'/unit_tester.php');
require_once(SIMPLETEST_DIR.'/mock_objects.php');
require_once(SIMPLETEST_DIR.'/reporter.php');
require_once(SIMPLETEST_DIR.'/HtmlReporterWithCoverage.php');

require_once(FRAMEWORK_DIR.'/core.php');

set_include_path(get_include_path().";".FRAMEWORK_DIR);

class Prado extends PradoBase
{
}

function __autoload($className)
{
	require_once($className.Prado::CLASS_FILE_EXT);
}

error_reporting(E_ALL);
restore_error_handler();


/**
 * PradoUnitTestCase class.
 *
 * Extends the simpletest UnitTestCase class to provide some fairly generic extra functionality.
 *
 * @author Alex Flint <alex@linium.net>
 */
class PradoUnitTestCase extends UnitTestCase {
	
	/**
	 * Tests whether the given code results in an appropriate exception being raised.
	 * @param string the PHP code to execute. must end with a semi-colon.
	 * @param string the type of exception that should be raised.
	 * @return boolean true
	 */
	public function assertException(string $code, string $exceptionType) {
		$ex = null;
		eval("try { $code } catch ($exceptionType \$e) { \$ex = \$e; }");
		$this->assertIsA($ex, $exceptionType);
	}
}



/**
 * Generate a class called MockTApplication to mock the TApplication class 
 * for the purpose of testing IModule objects.
 */
__autoload("TApplication");
Mock::generate("TApplication");

/**
 * ModuleTestCase class.
 *
 * Provides functionality designed to support testing of objects implementing the IModule
 * interface.
 *
 * Also provides some specific tests for IModule objects.
 *
 * @author Alex Flint <alex@linium.net>
 */
 
class ModuleTestCase extends PradoUnitTestCase {
	
	protected $module = null;
	protected $mockApplication = null;
	
	public function __construct() {
		$file = "";
		$tihs->mockApplication = new MockTApplication($file);
	}
	
	public function testGetSetID() {
		if ($this->module instanceof IModule) {
			$this->module->setId(123);
			$this->assertEqual($this->module->getId(123));
		}
	}
	
	/**
	 * Initializes $this->module by calling the init() method with the provided configuration.
	 * If no application object is provided then a mock application object $this->mockApplication.
	 * @param array optional. The configuration array to provide to the module. If none provided then
	 * 	    an empty array will be used.
	 * @param TApplication optional. The TApplication to pass to the init() function of the module.
	 *      If none provided then $this->mockApplication will be used.
	 */
	public function initModule($config=array(), $application=null) {
		if ($this->module instanceof IModule) {
			if (is_null($application)) {
				$application =& $this->mockApplication;
			}
			$this->module->init($config, $application);
		}
	}
}

?>