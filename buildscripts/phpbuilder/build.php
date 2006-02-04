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
	if($line==='' || $line[0]==='#')
		continue;
	echo 'adding '.FRAMEWORK_DIR.'/'.$line."\n";
	$input=file_get_contents(FRAMEWORK_DIR.'/'.$line);
	$input = strip_comments($input);
	$input=strtr($input,"\r",'');
	$input=preg_replace("/\s*(\n+\s*){2,}\s*/m","\n",$input);
	$input=preg_replace('/^Prado::using\([^\*]*?\);/mu','',$input);
	$input=preg_replace('/^(require|require_once)\s*\(.*?;/mu','',$input);
	$input=preg_replace('/^(include|include_once)\s*\(.*?;/mu','',$input);
	$input=preg_replace('/^\s*/m','',$input);

	//remove internal logging
	$input=preg_replace('/^\s*Prado::trace.*\s*;\s*$/mu','',$input);

	$output.=$input;
}

$output=str_replace('?><?php','',$output);

file_put_contents(FRAMEWORK_DIR.'/'.OUTPUT_FILE,$output);

function strip_comments($source)
{
  $tokens = token_get_all($source);
  /* T_ML_COMMENT does not exist in PHP 5.
   * The following three lines define it in order to
   * preserve backwards compatibility.
   *
   * The next two lines define the PHP 5-only T_DOC_COMMENT,
   * which we will mask as T_ML_COMMENT for PHP 4.
   */
  if (!defined('T_ML_COMMENT')) {
    @define('T_ML_COMMENT', T_COMMENT);
  } else {
    @define('T_DOC_COMMENT', T_ML_COMMENT);
  }
  $output = '';
  foreach ($tokens as $token) {
    if (is_string($token)) {
      // simple 1-character token
      $output .= $token;
    } else {
      // token array
      list($id, $text) = $token;
      switch ($id) {
        case T_COMMENT:
        case T_ML_COMMENT: // we've defined this
        case T_DOC_COMMENT: // and this
          // no action on comments
          break;
        default:
          // anything else -> output "as is"
          $output .= $text;
          break;
      }
    }
  }
  return $output;
}

?>