<?php

require(dirname(__FILE__).'/PradoTester.php');

$tester=new PradoTester(dirname(__FILE__).'/features/tests');
$tester->run(new SimpleReporter());

?>