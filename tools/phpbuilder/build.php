#!/usr/bin/php
<?php
/**
 * Prado build file.
 *
 * This script compresses a list of Prado PHP script files
 * and merges them into one for performance redistribution.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package Tools
 */

/**
 * The merged file name
 */
define('OUTPUT_FILE','pradolite.php');
/**
 * The framework directory
 */
define('FRAMEWORK_DIR',realpath(dirname(__FILE__).'/../../framework'));
/**
 * The file containing script list to be built
 */
define('SCRIPT_FILES',dirname(__FILE__).'/files.txt');

if(FRAMEWORK_DIR===false)
	die('Unable to determine the installation directory of Prado Framework.');
if(!is_file(SCRIPT_FILES))
	die('Unable to read '.SCRIPT_FILES.'.');

$output='';

$lines=file(SCRIPT_FILES);
foreach($lines as $line)
{
	$line=trim($line);
	if($line==='')
		continue;
	echo 'adding '.FRAMEWORK_DIR.'/'.$line."\n";
	$input=file_get_contents(FRAMEWORK_DIR.'/'.$line);
	$input=strtr($input,"\r",'');
	$input=preg_replace('/\/\*.*?\*\//s','',$input);
	$input=preg_replace('/^Prado::using\([^\*]*?\);/m','',$input);
	$input=preg_replace('/^(require|require_once)\s*\(.*?;/m','',$input);
	$input=preg_replace('/^(include|include_once)\s*\(.*?;/m','',$input);
	$output.=$input;
}

file_put_contents(FRAMEWORK_DIR.'/'.OUTPUT_FILE,$output);

?>