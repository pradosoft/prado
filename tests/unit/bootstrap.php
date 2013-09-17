<?php
/**
 * A few common settings for all unit tests.
 *
 * Also remember do define the @package attribute for your test class to make it appear under
 * the right package in unit test and code coverage reports.
 */

define('PRADO_TEST_RUN', true);
define('PRADO_FRAMEWORK_DIR', dirname(__FILE__).'/../../framework');
define('VENDOR_DIR', dirname(__FILE__).'/../../vendor');
set_include_path(PRADO_FRAMEWORK_DIR.PATH_SEPARATOR.get_include_path());

if (!@include_once VENDOR_DIR.'/autoload.php') {
    die('You must set up the project dependencies, run the following commands:
        wget http://getcomposer.org/composer.phar
        php composer.phar install');
}

require_once(PRADO_FRAMEWORK_DIR.'/prado.php');