<?php

require_once(dirname(__FILE__).'/../../framework/prado.php');

$application=new TApplication(dirname(__FILE__).'/protected/application.xml',dirname(__FILE__).'/protected/application.cache');
$application->run();

?>