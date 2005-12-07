<?php
/**
 * PRADO Requirements Checker script
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package prado
 */

/**
 * PRADO Requirements Checker script
 *
 * This script will check if your system meets the requirements for running PRADO.
 * It will check if you are running the right version of PHP, if you included
 * the right libraries and if your php.ini file settings are correct.
 *
 * This script is capable of displaying localized messages.
 * All messages are stored in messages.txt. A localized message file is named as
 * messsages-<language code>.txt, and it will be used when the client browser
 * chooses the corresponding language.
 * The script output uses a template named template.html.
 * Its localized version is stored in template-<language code>.html.
 */

/**
 * Includes Prado class file
 */
require_once(dirname(__FILE__).'/../framework/prado.php');

// TO BE CONFIRMED: PHP 5.1.0 has problem with I18N and L10N
/**
 * @var array List of requirements (required or not, check item, hint)
 */
$requirements = array(
	array(true,'version_compare(PHP_VERSION,"5.0.4",">=")','PHP version check','PHP 5.0.4 or higher required'),
	array(false,'extension_loaded("zlib")','Zlib check','Zlib extension optional'),
	array(false,'extension_loaded("sqlite")','SQLite check','SQLite extension optional'),
	array(false,'extension_loaded("memcache")','Memcache check','Memcache extension optional'),
);

$results = "<table class=\"result\">\n";
$conclusion = 0;
foreach($requirements as $requirement)
{
	list($required,$expression,$aspect,$hint)=$requirement;
	eval('$ret='.$expression.';');
	if($required)
	{
		if($ret)
			$ret='passed';
		else
		{
			$conclusion=1;
			$ret='error';
		}
	}
	else
	{
		if($ret)
			$ret='passed';
		else
		{
			if($conclusion!==1)
				$conclusion=2;
			$ret='warning';
		}
	}
	$results.="<tr class=\"$ret\"><td class=\"$ret\">".lmessage($aspect)."</td><td class=\"$ret\">".lmessage($hint)."</td></tr>\n";
}
$results .= '</table>';
if($conclusion===0)
	$conclusion=lmessage('all passed');
else if($conclusion===1)
	$conclusion=lmessage('failed');
else
	$conclusion=lmessage('passed with warnings');

$tokens = array(
	'%%Conclusion%%' => $conclusion,
	'%%Details%%' => $results,
	'%%Version%%' => $_SERVER['SERVER_SOFTWARE'].' <a href="http://www.pradosoft.com/">PRADO</a>/'.Prado::getVersion(),
	'%%Time%%' => strftime('%Y-%m-%d %H:%m',time()),
);

$lang = Prado::getPreferredLanguage();
$templateFile=dirname(__FILE__)."/template-$lang.html";
if(!is_file($templateFile))
	$templateFile=dirname(__FILE__).'/template.html';
if(($content=@file_get_contents($templateFile))===false)
	die("Unable to open template file '$templateFile'.");

header('Content-Type: text/html; charset=UTF-8');
echo strtr($content,$tokens);

/**
 * Returns a localized message according to user preferred language.
 * @param string message to be translated
 * @return string translated message
 */
function lmessage($token)
{
	static $messages=null;
	if($messages===null)
	{
		$lang = Prado::getPreferredLanguage();
		$msgFile=dirname(__FILE__)."/messages-$lang.txt";
		if(!is_file($msgFile))
			$msgFile=dirname(__FILE__).'/messages.txt';
		if(($entries=@file($msgFile))!==false)
		{
			foreach($entries as $entry)
			{
				@list($code,$message)=explode('=',$entry,2);
				$messages[trim($code)]=trim($message);
			}
		}
	}
	return isset($messages[$token])?$messages[$token]:$token;
}

?>