#!/usr/bin/php
<?php
/**
 * Javascript build file.
 *
 * This script compresses a list of javascript source files
 * and merges them into a few for redistribution.
 *
 * This script should be run from command line with PHP.
 * JRE 1.4 or above is required in order to run the js compression program.
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
 * @version $Revision: $  $Date: $
 * @package Tools
 */

/**
 * The compression command line
 */
define('COMPRESS_COMMAND','java -jar '.dirname(__FILE__).'/custom_rhino.jar -c %s > %s');
/**
 * The root directory for storing all source js files
 */
define('SOURCE_DIR',realpath(dirname(__FILE__).'/../../framework/Web/JavaScripts'));
/**
 * The directory for storing compressed js files
 */
define('TARGET_DIR',realpath(dirname(__FILE__).'/../../framework/Web/JavaScripts/js'));

if(SOURCE_DIR===false || TARGET_DIR===false)
	die('Unable to determine the build path.');
if(!is_writable(TARGET_DIR))
	die('Unable to create files under '.TARGET_DIR.'.');

/**
 * list of js library files to be compressed and built
 */
$libraries = array(
	//base javascript functions
	'base.js' => array(
		'prototype/prototype.js',
		'prototype/compat.js',
		'prototype/base.js',
		'extended/base.js',
		'extended/util.js',
		'prototype/string.js',
		'extended/string.js',
		'prototype/enumerable.js',
		'prototype/array.js',
		'extended/array.js',
		'prototype/hash.js',
		'prototype/range.js',
		'extended/functional.js',
		'base/prado.js',
		'base/postback.js',
		'base/focus.js',
		'base/scroll.js'
	),
	//dom functions
	'dom.js' => array(
		'prototype/dom.js',
		'extended/dom.js',
		'prototype/form.js',
		'prototype/event.js',
		'extended/event.js',
		'prototype/position.js',
		'extra/getElementsBySelector.js',
		'extra/behaviour.js',
		'effects/util.js'
	),
	//effects
	'effects.js' => array(
		'effects/effects.js'
	),
	//controls
	'controls.js' => array(
		'effects/controls.js',
		'effects/dragdrop.js',
		'base/controls.js'
	),
	//logging
	'logger.js' => array(
		'extra/logger.js',
	),
	//ajax
	'ajax.js' => array(
		'prototype/ajax.js',
		'base/ajax.js',
		'base/json.js'
	),
	//rico
	'rico.js' => array(
		'effects/rico.js'
	),
	//javascript templating
	'template.js' => array(
		'extra/tp_template.js'
	),
	//validator
	'validator.js' => array(
		'base/validation.js',
		'base/validators.js'
	),
	//date picker
	'datepicker.js' => array(
		'base/datepicker.js'
	)
);

/**
 * Collect specific libraries to be built from command line
 */
$requestedLibs=array();
for($i=1;$i<$argc;++$i)
	$requestedLibs[]=$argv[$i].'.js';

/**
 * loop through all target files and build them one by one
 */
foreach($libraries as $libFile => $sourceFiles)
{
	if(!empty($requestedLibs) && !in_array($libFile,$requestedLibs))
		continue;
	$libFile=TARGET_DIR.'/'.$libFile;
	echo "\nBuilding $libFile...\n";
	$contents='';
	foreach($sourceFiles as $sourceFile)
	{
		$sourceFile=SOURCE_DIR.'/'.$sourceFile;
		if(!is_file($sourceFile))
			echo "Source file not found: $sourceFile\n";
		$tempFile=$sourceFile.'.tmp';
		$command=sprintf(COMPRESS_COMMAND,$sourceFile,$tempFile);
		echo "...adding $sourceFile\n".
		system($command);
		$contents.=file_get_contents($tempFile);
		@unlink($tempFile);
	}
	file_put_contents($libFile,$contents);
}

?>