<?php

$basePath=dirname(__FILE__);
require_once($basePath.'/../../framework/prado.php');
$application=new TApplication($basePath.'/protected/application.xml');
$application->run();

?>