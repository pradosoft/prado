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

?>