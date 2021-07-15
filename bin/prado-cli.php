<?php

/**
 * Prado command line developer tools.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @author Brad Anderson <belisoful@icloud.com> shell refactor
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

if (!isset($_SERVER['argv']) || php_sapi_name() !== 'cli') {
	die('Must be run from the command line');
}

// Locate composer's autoloader
if (file_exists($autoloader = realpath(__DIR__ . '/../../../autoload.php'))) {
	// if we are running from inside an application's vendor/ directory, get out of pradosoft/prado/bin/
	include($autoloader);
} elseif (file_exists($autoloader = realpath(__DIR__ . '/../vendor/autoload.php'))) {
	// if we are running inside a prado repo checkout, get out of bin/
	include($autoloader);
}

restore_exception_handler();

$app_dir = dirname(__DIR__, 4);

$args = $_SERVER['argv'];
foreach ($_SERVER['argv'] as $i => $arg) {
	$arg = explode('=', $arg);
	if ($arg[0] === '-d' || $arg[0] === '--directory') {
		$app_dir = $arg[1] ?? '';
		unset($args[$i]);
		$args = array_values($args);
		break;
	}
}

$_SERVER['SCRIPT_FILENAME'] = dirname($app_dir) . DIRECTORY_SEPARATOR . 'index.php';
$app = new Prado\Shell\TShellApplication($app_dir);
$app->run($args);
