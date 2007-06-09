<?php
/**
 * A few common settings for all unit tests.
 *
 * This file should be included in all unit tests with absolute path to be able to run it
 * from the command line with "phpunit" like this:
 *
 * require_once dirname(__FILE__).'/path/../to/../phpunit.php';
 *
 * Also remember do define the @package attribute for your test class to make it appear under
 * the right package in unit test and code coverage reports.
 */
define('PRADO_FRAMEWORK_DIR', dirname(__FILE__).'/../../framework');
set_include_path(PRADO_FRAMEWORK_DIR.':'.get_include_path());

require_once dirname(__FILE__).'/Prado.php';
require_once PRADO_FRAMEWORK_DIR.'/prado.php';
require_once 'PHPUnit/Framework/TestCase.php';
require_once 'PHPUnit/Framework/IncompleteTestError.php';
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';
require_once 'PHPUnit/Util/Filter.php';
?>
