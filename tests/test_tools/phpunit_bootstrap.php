<?php
/**
 * A few common settings for all unit tests.
 *
 * Also remember do define the @package attribute for your test class to make it appear under
 * the right package in unit test and code coverage reports.
 */

defined('PRADO_TEST_RUN') || define('PRADO_TEST_RUN', true);
// coverage tests waste a lot of memory!
ini_set('memory_limit', '1G');

require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ . '/../../framework/Prado.php');

// for FunctionalTests
require_once(__DIR__ . '/PradoGenericSelenium2Test.php');
