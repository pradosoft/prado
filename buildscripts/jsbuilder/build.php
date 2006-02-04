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

//compress using a script, has more options but lesss robut
//define('COMPRESS_COMMAND','java -jar '.dirname(__FILE__).'/custom_rhino.jar packer.js %s > %s');

//compress using build-in engine, very robust.
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
	'prado.js' => array(

		//base javascript functions
		'prototype/prototype.js',
		'prototype/base.js',
		'extended/util.js',
		'extended/base.js',
		'prototype/string.js',
		'extended/string.js',
		'prototype/enumerable.js',
		'prototype/array.js',
		'prototype/hash.js',
		'prototype/range.js',

		//dom functions
		'prototype/dom.js',
		'extended/dom.js',
		'prototype/form.js',
		'prototype/event.js',
		'extended/event.js',
		'prototype/position.js',

		//build dom elements with DIV, A, UL, etc functions
		'effects/builder.js',
		'extended/builder.js',

//		'extra/getElementsBySelector.js',
//		'extra/behaviour.js',

		'extended/date.js',
	
		//prado core
		'prado/prado.js',
		'prado/form.js',
		'prado/element.js',
		'prado/controls.js'
	),

	//effects
	'effects.js' => array(
		'effects/effects.js'
	),
	//active controls
	'ajax.js' => array(
		'prototype/ajax.js',
		'prado/ajax.js',
		'extra/json.js',
		'effects/controls.js',
		'effects/dragdrop.js',
		'effects/slider.js',
		'prado/activecontrols.js'
	),
	//logging
	'logger.js' => array(
		'extra/logger.js',
	),

	//rico
	'rico.js' => array(
		'rico/rico.js',
		'rico/extension.js'
	),

	//validator
	'validator.js' => array(
		'prado/validation.js',
		'prado/validators.js'
	),

	//date picker
	'datepicker.js' => array(
		'datepicker/datepicker.js'
	),

	//color picker
	'colorpicker.js' => array(
		'rico/colors.js',
		'colorpicker/colorpicker.js'
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
		
		echo "...adding $sourceFile\n";
		$contents.=file_get_contents($sourceFile)."\n\n";		
	}
	$tempFile=$libFile.'.tmp';
	file_put_contents($tempFile,$contents);
	$command=sprintf(COMPRESS_COMMAND,$tempFile,$libFile);
	system($command);
	@unlink($tempFile);
}

?>