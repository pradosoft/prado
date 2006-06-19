<?php
/**
 * @package phpDocumentor
 */
//
// +------------------------------------------------------------------------+
// | phpDocumentor                                                          |
// +------------------------------------------------------------------------+
// | Copyright (c) 2000-2003 Joshua Eichorn, Gregory Beaver                 |
// | Email         jeichorn@phpdoc.org, cellog@phpdoc.org                   |
// | Web           http://www.phpdoc.org                                    |
// | Mirror        http://phpdocu.sourceforge.net/                          |
// | PEAR          http://pear.php.net/package-info.php?pacid=137           |
// +------------------------------------------------------------------------+
// | This source file is subject to version 3.00 of the PHP License,        |
// | that is available at http://www.php.net/license/3_0.txt.               |
// | If you did not receive a copy of the PHP license and are unable to     |
// | obtain it through the world-wide-web, please send a note to            |
// | license@php.net so we can mail you a copy immediately.                 |
// +------------------------------------------------------------------------+
//


// set up include path so we can find all files, no matter what
$a = explode('/',str_replace('\\','/',dirname(realpath(__FILE__))));
array_pop($a);
$GLOBALS['_phpDocumentor_install_dir'] = join('/',$a);
// add my directory to the include path, and make it first, should fix any errors
if (substr(PHP_OS, 0, 3) == 'WIN')
ini_set('include_path',$GLOBALS['_phpDocumentor_install_dir'].';'.ini_get('include_path'));
else
ini_set('include_path',$GLOBALS['_phpDocumentor_install_dir'].':'.ini_get('include_path'));

/**
 * All command-line handling from previous version has moved to here
 *
 * Many settings also moved to phpDocumentor.ini
 */
include("phpDocumentor/Setup.inc.php");

$phpdoc = new phpDocumentor_setup;
$phpdoc->readCommandLineSettings();
$phpdoc->setupConverters();
$phpdoc->createDocs();
?>
