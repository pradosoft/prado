<?php

include_once '../test_tools/unit_tests.php';
$test_cases = dirname(__FILE__)."/";

$tester = new PradoUnitTester($test_cases);
$tester->run(new HtmlReporter());

?>