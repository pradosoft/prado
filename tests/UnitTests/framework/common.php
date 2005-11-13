<?php

if(!defined('FRAMEWORK_DIR'))
	define('FRAMEWORK_DIR',realpath(dirname(__FILE__).'/../../../framework'));
if(!defined('SIMPLETEST_DIR'))
	define('SIMPLETEST_DIR',realpath(dirname(__FILE__).'/../simpletest'));

require_once(SIMPLETEST_DIR.'/unit_tester.php');
require_once(SIMPLETEST_DIR.'/reporter.php');
require_once(SIMPLETEST_DIR.'/HtmlReporterWithCoverage.php');

require_once(FRAMEWORK_DIR.'/core.php');

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
 * PradoTestCase class.
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
	public function assertException(string $code, string $exception) {
		$pass = false;
		$code = "
try {
	$code
} catch ($exception \$e) {
	\$pass = true;
}";
		eval($code);
		if ($pass) {
			$this->pass();
		} else {
			$this->fail("Code did not produce correct exception (wanted $exception, got something else");
		}
	}
}
		
		

?>