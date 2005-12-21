<?php

define('APPLICATION_PATH',dirname(__FILE__));

if(!is_writable(APPLICATION_PATH.'/protected/Data'))
	die('Please make sure that the directory "'.APPLICATION_PATH.'/protected/Data'.'" is writable by Web server process.');
if(!is_writable(APPLICATION_PATH.'/assets'))
	die('Please make sure that the directory "'.APPLICATION_PATH.'/assets'.'" is writable by Web server process.');

require_once(APPLICATION_PATH.'/../../framework/prado.php');
$application=new TApplication(APPLICATION_PATH.'/protected/Data/application.xml');
$application->run();

?>