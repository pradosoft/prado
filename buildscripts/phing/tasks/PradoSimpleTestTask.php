<?php
require_once 'phing/Task.php';
require_once 'phing/tasks/ext/simpletest/SimpleTestTask.php';

/**
 * Task to run PRADO unit tests
 */
class PradoSimpleTestTask extends SimpleTestTask
{
	private $_appdir;

	public function setAppdir($value)
	{
		$this->_appdir=$value;
	}

	function init()
	{
		$tools= realpath(dirname(__FILE__).'/../../../tests/test_tools/');
		include_once "$tools/unit_tests.php";

		if (!class_exists('SimpleReporter',false))
			throw new BuildException("SimpleTestTask depends on SimpleTest package being installed.", $this->getLocation());

		require_once 'phing/tasks/ext/simpletest/SimpleTestCountResultFormatter.php';
		require_once 'phing/tasks/ext/simpletest/SimpleTestFormatterElement.php';
	}

	function main()
	{
		if($this->_appdir)
		{
			$app = new TShellApplication($this->_appdir);
			$app->run();
		}
		parent::main();
	}
}

?>