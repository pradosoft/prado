<?php

require_once(dirname(__FILE__).'/../../framework/prado.php');

$application=new TApplication('protected/application.xml','protected/application.cache');
$application->run();

?>