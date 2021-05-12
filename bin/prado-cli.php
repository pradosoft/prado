<?php

/**
 * Prado command line developer tools.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

if (!isset($_SERVER['argv']) || php_sapi_name() !== 'cli') {
	die('Must be run from the command line');
}

// Locate composer's autoloader
if (file_exists($autoloader = realpath(__DIR__ . '/../vendor/autoload.php'))) {
	// if we are running inside a prado repo checkout, get out of bin/
	include($autoloader);
} elseif (file_exists($autoloader = realpath(__DIR__ . '/../../../autoload.php'))) {
	// if we are running from inside an application's vendor/ directory, get out of pradosoft/prado/bin/
	include($autoloader);
}

use Prado\Shell\TShellInterpreter;

use Prado\Shell\Actions\TActiveRecordGenAction;
use Prado\Shell\Actions\TActiveRecordGenAllAction;
use Prado\Shell\Actions\TAppAction;
use Prado\Shell\Actions\TFlushCachesAction;
use Prado\Shell\Actions\TPphShellAction;

restore_exception_handler();

//register action classes
TShellInterpreter::getInstance()->addActionClass('Prado\\Shell\\Actions\\TAppAction');
TShellInterpreter::getInstance()->addActionClass('Prado\\Shell\\Actions\\TFlushCachesAction');
TShellInterpreter::getInstance()->addActionClass('Prado\\Shell\\Actions\\TPphShellAction');
TShellInterpreter::getInstance()->addActionClass('Prado\\Shell\\Actions\\TActiveRecordGenAction');
TShellInterpreter::getInstance()->addActionClass('Prado\\Shell\\Actions\\TActiveRecordGenAllAction');

//run it;
TShellInterpreter::getInstance()->run($_SERVER['argv']);
