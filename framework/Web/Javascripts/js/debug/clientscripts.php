<?php
/**
 * This file compresses the javascript files using GZip
 *
 * Todo:
 *  - Add local file cache for the GZip:ed version.
 */

$debugMode=(isset($_GET['mode']) && $_GET['mode']==='debug');

// if debug mode, js is not cached; otherwise cached for 10 days.
$expiresOffset = $debugMode ? -10000 : 3600 * 24 * 10; //no cache

//allowed libraries
$library = array('prado', 'effects', 'ajax', 'validator', 'logger', 'datepicker', 'rico', 'colorpicker');

$param = isset($_GET['js']) ? $_GET['js'] : '';

//check for proper matching parameters, otherwise exit;
if(preg_match('/(\w)+(,\w+)*/', $param)) $js = explode(',', $param); else exit();
foreach($js as $lib) if(!in_array($lib, $library)) exit();

// Only gzip the contents if clients and server support it
if (isset($_SERVER['HTTP_ACCEPT_ENCODING']))
	$encodings = explode(',', strtolower($_SERVER['HTTP_ACCEPT_ENCODING']));
else
	$encodings = array();

// Check for gzip header or northon internet securities
if ((in_array('gzip', $encodings) || isset($_SERVER['---------------']))
		&& function_exists('ob_gzhandler') && !ini_get('zlib.output_compression')
		&& ini_get('output_handler') != 'ob_gzhandler')
	ob_start("ob_gzhandler");

// Output rest of headers
header('Content-type: text/javascript; charset: UTF-8');
// header("Cache-Control: must-revalidate");
header('Vary: Accept-Encoding'); // Handle proxies
header('Expires: ' . @gmdate('D, d M Y H:i:s', @time() + $expiresOffset) . ' GMT');

if ($debugMode)
{
	foreach($js as $lib)
	{
		$file = realpath($lib.'.js');
		if(is_file($file))
			echo file_get_contents($file);
		else //log missings files to console logger
		{
			echo 'setTimeout(function(){ if(Logger) Logger.error("Missing file", "'.$lib.'.js"); }, 1000);';
			error_log("Unable to find asset file {$lib}.js");
		}
	}
}
else
{
	foreach($js as $lib)
		echo file_get_contents($lib.'.js');
}

?>