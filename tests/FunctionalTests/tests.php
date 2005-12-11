<?php

require_once(dirname(__FILE__).'/../../framework/prado.php');
require_once(dirname(__FILE__).'/config.php');


$application=new TApplication(dirname(__FILE__).'/framework/application.xmla');
$application->run();

?>