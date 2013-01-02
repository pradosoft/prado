<?php
require_once 'phing/Task.php';
require_once 'phing/tasks/ext/phpunit/PHPUnitTask.php';
require 'PHPUnit/Autoload.php';

/**
 * Task to run PRADO unit tests
 */	
class PradoTestTask extends PHPUnitTask
{
}

?>