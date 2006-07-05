<?php

require('../../test_tools/functional_tests.php');

$tester=new PradoFunctionalTester(dirname(__FILE__).'/validators/tests');
$tester->run(new SimpleReporter());

?>