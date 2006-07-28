<?php

$basePath=dirname(__FILE__);
$frameworkPath='../../framework/pradolite.php';
$assetsPath=$basePath."/assets";
$runtimePath=$basePath."/protected/runtime";

if(!is_file($frameworkPath))
	die("Unable to find prado framework path $frameworkPath.");
if(!is_writable($assetsPath))
	die("Please make sure that the directory $assetsPath is writable by Web server process.");
if(!is_writable($runtimePath))
	die("Please make sure that the directory $runtimePath is writable by Web server process.");

require_once($frameworkPath);

function h($text)
{
	$app = Prado::getApplication()->getGlobalization();
	$charset = $app ? $app->getCharset() : 'UTF-8';
	return htmlentities($text, ENT_QUOTES, $charset);
}

$application=new TApplication;
$application->run();

?>