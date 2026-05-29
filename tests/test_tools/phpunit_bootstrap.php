<?php
/**
 * A few common settings for all unit tests.
 *
 * Also remember do define the @package attribute for your test class to make it appear under
 * the right package in unit test and code coverage reports.
 */

require_once(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ . '/../../framework/Prado.php');
require_once(__DIR__ . '/../unit/PradoUnit.php');

// Capture the process-global baseline (superglobals, cwd, timezone,
// error_reporting, include_path) BEFORE the bootstrap TApplication runs and
// mutates any of it. Tests can restore via PradoUnit::restoreInitialState().
PradoUnit::captureInitialState();

//  Use the test app at 'Security/app'
$relativeAppPath = '/../unit/Security/app';
$appPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . $relativeAppPath);

// For unit tests requiring a global TApplication object,
//  construct -which sets {@see Prado::getApplication()}- but do not run
$application = new \Prado\TApplication($appPath);

/*
// ## DEPRECATED
// classes for Selenium functional tests
require_once(__DIR__ . '/PradoGenericSelenium2Test.php');
require_once(__DIR__ . '/PradoTestListener.php');
*/
