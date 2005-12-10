<?php

//error_reporting(E_ALL);
//restore_error_handler();

$SIMPLE_TEST = dirname(__FILE__).'/../UnitTests';

require_once($SIMPLE_TEST.'/simpletest/unit_tester.php');
require_once($SIMPLE_TEST.'/simpletest/web_tester.php');
require_once($SIMPLE_TEST.'/simpletest/mock_objects.php');
require_once($SIMPLE_TEST.'/simpletest/reporter.php');
require(dirname(__FILE__).'/selenium/php/selenium.php');

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
		return dirname(__FILE__).'/framework/';
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
	public function runApplication($appUrl='tests.php', $file=null, $class='PradoApplicationTester')
	{
		if(is_null($file))
			$file = $this->tests_directory().'/application.xml';
		$app = new $class($file, $this, $appUrl);
		$app->run();
	}

	//file patterns to accept for test
	public function acceptPattern()
	{
		return '/test(\w+)\.php/';
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

require_once(PradoTestConfig::framework().'/prado.php');
require_once(PradoTestConfig::framework().'/TApplication.php');

class PradoApplicationTester extends TApplication
{
	protected $testConfig;


	public function __construct($spec, $config, $appUrl)
	{
		$this->testConfig = $config;
		parent::__construct($spec);
		$request = new FunctionTestRequest();
		$request->init($this, null);
		$request->setAppUrl($appUrl);
		$this->setRequest($request);
		$response = new FunctionTestResponse();
		$response->init($this, null);
		$this->setResponse($response);
	}

	public function run()
	{
		$this->applyDefaultExceptionHandlers();
		$this->initApplication($this->getConfigurationFile(),null);
	}

	public function getServiceConfig()
	{
		$config=new TApplicationConfiguration;
		$config->loadFromFile($this->getConfigurationFile());
		return $config->getService($this->getTestServiceID());
	}

	public function getTestServiceID()
	{
		if(($serviceID=$this->getRequest()->getServiceID())===null)
			$serviceID=self::DEFAULT_SERVICE;
		return $serviceID;
	}

	public function getTestPage($file)
	{
		$parameter = $this->getTestServiceParameter($file);
		$this->getRequest()->setServiceParameter($parameter);
		$service = $this->getService();
		$config = $this->getServiceConfig();
		$service->init($this, $config[2]);
		$service->run();
		return $service->getRequestedPage();
	}

	protected function getTestServiceParameter($file)
	{
		$file = realpath($file);
		$base = realpath($this->testConfig->tests_directory());
		$search = array($base, '.php');
		$replace = array('', '');
		$pagePath = str_replace($search, $replace, $file);
		$separator = array("/", "\\");
		if(in_array($pagePath[0], $separator))
			$pagePath = substr($pagePath, 1);
		return str_replace($separator, array('.','.'), $pagePath);
	}
}

class FunctionTestRequest extends THttpRequest
{
	protected $appUrl;

	public function setServiceParameter($parameter)
	{
		parent::setServiceParameter($parameter);
	}

	public function setAppUrl($url)
	{
		$this->appUrl = $url;
	}

	public function getApplicationPath()
	{
		return $this->appUrl;
	}

	public function getTestUrl()
	{
		$serviceParam = $this->getServiceParameter();
		$serviceID= prado::getApplication()->getTestServiceID();
		return $this->constructUrl($serviceID, $serviceParam);
	}
}

class FunctionTestResponse extends THttpResponse
{
	public function write($str)
	{

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
			$file = realpath($path.'/'.$entry);
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