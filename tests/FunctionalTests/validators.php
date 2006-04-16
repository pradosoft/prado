<?php

require(dirname(__FILE__).'/PradoTester.php');

$tester=new PradoTester(dirname(__FILE__).'/validators/tests');
$tester->run(new SimpleReporter());

?>