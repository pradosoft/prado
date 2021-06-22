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

use Prado\Shell\TShellApplication;

$app_dir = dirname(__DIR__, 4);

foreach ($_SERVER['argv'] as $i => $arg) {
	if (strncasecmp($arg, '-d', 2) === 0) {
		$app_dir = substr($arg, 2);
		unset($_SERVER['argv'][$i]);
		if (!$app_dir) {
			$app_dir = $_SERVER['argv'][$i + 1] ?? null;
			unset($_SERVER['argv'][$i + 1]);
		} else {
			if ($app_dir[0] === '=') {
				$app_dir = substr($app_dir, 1);
			}
		}
		$_SERVER['argv'] = array_values($_SERVER['argv']);
		break;
	}
}

$_SERVER['SCRIPT_FILENAME'] = dirname($app_dir) . DIRECTORY_SEPARATOR . 'index.php';
$app = new TShellApplication($app_dir);
$app->run();
