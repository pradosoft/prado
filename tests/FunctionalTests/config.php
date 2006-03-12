<?php

//error_reporting(E_ALL);
//restore_error_handler();

$SIMPLE_TEST = dirname(__FILE__).'/../UnitTests';

require_once($SIMPLE_TEST.'/simpletest/unit_tester.php');
require_once($SIMPLE_TEST.'/simpletest/web_tester.php');
require_once($SIMPLE_TEST.'/simpletest/mock_objects.php');
require_once($SIMPLE_TEST.'/simpletest/reporter.php');
require(dirname(__FILE__).'/selenium/php/selenium.php');
//require_once(PradoTestConfig::framework().'/prado.php');

/** test configurations , OVERRIDE to suite your enviornment !!! **/
class PradoTestConfig
{
	//directories containing test files
	public function unit_test_groups()
	{
		return array();
	}

	//test directory base
	public function tests_directory()
	{
		return dirname(__FILE__).'/protected/';
	}

	//prado frame work directory
	public static function framework()
	{
		return realpath(dirname(__FILE__).'/../../framework/');
	}

	//do test that require mysql database connection?
	public function doMySQLTests()
	{
		return false;
	}

	//run the prado application
	public function runApplication($appUrl='tests.php', $class='PradoApplicationTester')
	{
		$app = new $class($this, $appUrl);
		$app->run();
	}

	//file patterns to accept for test
	public function acceptPattern()
	{
		return '/\w+\.php/';
	}

	public function rejectPattern()
	{
		return null;
	}

	public function getTestCase()
	{
		return isset($_GET['file']) ? $_GET['file'] : '';
	}
}

//set up the PradoApplication Testing stub.

class PradoApplicationTester
{
	protected $appUrl;
	protected $testConfig;

	public function __construct($config, $appUrl)
	{
		$this->appUrl = $appUrl;
		$this->testConfig = $config;
	}

	public function run()
	{
	}

	public function getTestPage($file)
	{
		$parameter = $this->getTestServiceParameter($file);
		return $this->appUrl.'?page='.$parameter;
	}

	protected function getTestServiceParameter($file)
	{
		$file = strtr(realpath($file),'\\','/');
		$base = strtr(realpath($this->testConfig->tests_directory().'/pages/'),'\\','/');
		$search = array($base, '.php');
		$replace = array('', '');
		$pagePath = str_replace($search, $replace, $file);
		return strtr(trim($pagePath,'/'),'/','.');
	}
}

/** set up the tests **/

class PradoSimpleTester
{
	protected $tester;

	function __construct($tester)
	{
		$this->tester = $tester;
		$this->tester->runApplication();
	}

	function getTests($name='All Tests')
	{
		$unit_tests = new GroupTest($name);

		foreach($this->tester->unit_test_groups() as $group => $dir)
		{
			$unit_tests->addTestCase($this->testSuits($group, $dir));
		}
		return $unit_tests;
	}

	protected function testSuits($group, $path)
	{
		$suite = new GroupTest($group);
		$dir = dir($path);

		while (false !== ($entry = $dir->read()))
		{
			$file = strtr(realpath($path.'/'.$entry),'\\','/');
			$matchFile = $this->tester->getTestCase();
			if(is_file($file) && $this->filePatternMatch($file))
			{
				if(empty($matchFile) ||
					(!empty($matchFile)
						&& is_int(strpos($file, $matchFile))))
							$suite->addTestFile($file);
			}

		}
		$dir->close();
		return $suite;
	}

	protected function filePatternMatch($file)
	{
		$accept = $this->tester->acceptPattern();
		$reject = $this->tester->rejectPattern();

		$valid = true;
		if(!is_null($accept))
			$valid = $valid && preg_match($accept, $file);
		if(!is_null($reject))
			$valid = $valid && !preg_match($reject, $file);
		return $valid;
	}
}

?>