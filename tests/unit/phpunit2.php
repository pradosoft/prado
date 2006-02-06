<?php
/**
 * A few common settings for all unit tests.
 */
define('PRADO_FRAMEWORK_DIR', dirname(__FILE__).'/../../framework');
set_include_path(PRADO_FRAMEWORK_DIR.':'.get_include_path());
require_once PRADO_FRAMEWORK_DIR.'/prado.php';
require_once 'PHPUnit2/Framework/TestCase.php';
require_once 'PHPUnit2/Framework/IncompleteTestError.php';
?>
