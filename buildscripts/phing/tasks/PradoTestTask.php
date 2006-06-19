<?php
require_once 'phing/Task.php';
require_once 'phing/tasks/ext/phpunit2/PHPUnit2Task.php';

/**
 * Task to run PRADO unit tests
 */	
class PradoTestTask extends PHPUnit2Task
{
	function init()
	{
		$phpunit2_path = realpath(dirname(__FILE__).'/../..');
		set_include_path(get_include_path().PATH_SEPARATOR.$phpunit2_path);		
		parent::init();
	}
}

?>