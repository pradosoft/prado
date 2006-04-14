<?php

//define simple test location
define('SIMPLE_TEST', realpath('../../../tests/UnitTests/simpletest'));

//define prado framework location
define('PRADO', realpath('../../../framework'));

//define directory that contains business objects
define('MY_MODELS', realpath('../protected/business-objects'));

require_once(SIMPLE_TEST.'/unit_tester.php');
require_once(SIMPLE_TEST.'/reporter.php');
require_once(PRADO.'/prado.php');
require_once(MY_MODELS.'/Person.php');

//supress strict warnings 
error_reporting(E_ALL);

//import Data mapper
Prado::using('System.DataAccess.SQLMap.TMapper');

//Add tests
$test = new GroupTest('SQLMap Tutorial tests');
$test->addTestFile('PersonTest.php');
if(SimpleReporter::inCli())
	$reporter = new TextReporter();
else
	$reporter = new HtmlReporter();
$test->run($reporter);


?>