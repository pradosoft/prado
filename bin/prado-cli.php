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

function checkForAppConfig($path)
{
	if (false !== ($xml = realpath($path . DIRECTORY_SEPARATOR . 'application.xml')) && is_file($xml)) {
		return true;
	}
	if (false !== ($php = realpath($path . DIRECTORY_SEPARATOR . 'application.php')) && is_file($php)) {
		return true;
	}
	return false;
}

$found = false;

//check internal composer vendor for application
if (!checkForAppConfig($app_dir = dirname(__DIR__, 4))) {
	//check current working directory for application
	if (!checkForAppConfig($app_dir = getcwd())) {
		//check current working directory . '/protected' for application
		if (checkForAppConfig($app_dir .= DIRECTORY_SEPARATOR . 'protected')) {
			$found = true;
		}
	} else {
		$found = true;
	}
} else {
	$found = true;
}

$dir_option = false;
$args = $_SERVER['argv'];
foreach ($_SERVER['argv'] as $i => $arg) {
	$arg = explode('=', $arg);
	if ($arg[0] === '-d' || $arg[0] === '--directory') {
		$dir_option = true;
		$app_dir = $arg[1] ?? '';
		unset($args[$i]);
		$args = array_values($args);
		break;
	}
}

if (!$found && !$dir_option) {
	$writer = new Prado\Shell\TShellWriter(new Prado\IO\TOutputWriter());
	$writer->writeError("Application could not be found.  Specify the app config directory with '-d=/path/to/app/protected'.");
	$writer->flush();
	exit();
} elseif ($dir_option && !checkForAppConfig($app_dir)) {
	if (!checkForAppConfig($app_dir . DIRECTORY_SEPARATOR . 'protected')) {
		$writer = new Prado\Shell\TShellWriter(new Prado\IO\TOutputWriter());
		$writer->writeError("Application could not be found in directory '{$app_dir}'.");
		$writer->flush();
		exit();
	} else {
		$app_dir .= DIRECTORY_SEPARATOR . 'protected';
	}
}

$_SERVER['SCRIPT_FILENAME'] = dirname($app_dir) . DIRECTORY_SEPARATOR . 'index.php';
$app = new Prado\Shell\TShellApplication($app_dir);
$app->run($args);
