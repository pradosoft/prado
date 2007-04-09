#!/usr/bin/php
<?php
/**
 * Javascript build file.
 *
 * This script compresses a list of javascript source files
 * and merges them into a few for redistribution.
 *
 * By default, all libraries will be built.
 * You may, however, specify one or several to be built (to save time during development).
 * To do so, pass the library names (without .js) as command line arguments.
 * For example: php build.php base dom
 *
 * @author Xiang Wei Zhuo <weizhuo@gmail.com>, Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package Tools
 */

/**
 * The root directory for storing all source js files
 */
define('SOURCE_DIR',realpath(dirname(__FILE__).'/../../framework/Web/Javascripts/source'));
/**
 * The directory for storing compressed js files
 */
define('TARGET_DIR',realpath(dirname(__FILE__).'/../../framework/Web/Javascripts/js'));

define('JSMIN_AS_LIB',true);

include(dirname(__FILE__).'/jsmin.php');

if(SOURCE_DIR===false || TARGET_DIR===false)
	die('Unable to determine the build path.');
if(!is_writable(TARGET_DIR))
	die('Unable to create files under '.TARGET_DIR.'.');

/**
 * list of js library files to be compressed and built
 */
$libraries = array(
	'prado.js' => array(
		'prototype/prototype.js',
		'scriptaculous/builder.js',
		'prado/prado.js',
		'prado/scriptaculous-adapter.js',
		'prado/controls/controls.js',
		'prado/ratings/ratings.js',
	),

	'effects.js' => array(
		'scriptaculous/effects.js'
	),

	'logger.js' => array(
		'prado/logger/logger.js',
	),

	'validator.js' => array(
		'prado/validator/validation3.js'
	),

	'datepicker.js' => array(
		'prado/datepicker/datepicker.js'
	),

	'colorpicker.js' => array(
		'prado/colorpicker/colorpicker.js'
	),

	'ajax.js' => array(
		'scriptaculous/controls.js',
		'prado/activecontrols/json.js',
		'prado/activecontrols/ajax3.js',
		'prado/activecontrols/activecontrols3.js',
		'prado/activecontrols/inlineeditor.js',
		'prado/activeratings/ratings.js'
	)
);

/**
 * Collect specific libraries to be built from command line
 */
$requestedLibs=array();
for($i=1;$i<$argc;++$i)
	$requestedLibs[]=$argv[$i].'.js';

$builds = 0;
/**
 * loop through all target files and build them one by one
 */
foreach($libraries as $jsFile => $sourceFiles)
{
	if(!empty($requestedLibs) && !in_array($jsFile,$requestedLibs))
		continue;
	//$libFile=TARGET_DIR.'/'.$jsFile;
	echo "\nBuilding $jsFile...\n";
	$contents='';
	foreach($sourceFiles as $sourceJsFile)
	{
		$sourceFile=SOURCE_DIR.'/'.$sourceJsFile;
		if(!is_file($sourceFile))
			echo "Source file not found: $sourceFile\n";

		echo "...adding $sourceJsFile\n";
		$contents.=file_get_contents($sourceFile)."\n\n";
	}
	$debugFile=TARGET_DIR.'/debug/'.$jsFile;
	$compressFile=TARGET_DIR.'/compressed/'.$jsFile;
	file_put_contents($debugFile,$contents);
	$jsMin = new JSMin($debugFile, $compressFile);
	$jsMin -> minify();
	unset($jsMin);
	//@unlink($tempFile);
	echo "Saving file {$jsFile}\n";
	$builds++;
}

?>