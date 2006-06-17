<?php

require(dirname(__FILE__).'/PradoTester.php');

$tester=new PradoTester(dirname(__FILE__).'/active-controls/tests');
$tester->run(new SimpleReporter());

?>