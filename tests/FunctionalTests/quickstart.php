<?php

require(dirname(__FILE__).'/PradoTester.php');

$tester=new PradoTester(dirname(__FILE__).'/quickstart');
$tester->run(new SimpleReporter());

?>