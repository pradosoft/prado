<?php

require_once(dirname(__FILE__).'/../unit_tester.php');
require_once(dirname(__FILE__).'/../reporter.php');

class TestOfLogging extends UnitTestCase
{
    function testCreatingNewFile() {
        $this->assertFalse(false);
        //$log->message('Should write this to a file');
        $this->assertTrue(true);
    }
}

$test = new TestOfLogging();
$test->run(new HtmlReporter());

?>