<?php
/**
 * Selenium PHP driver. Saves the test command in a "out" queue (in session),
 * and for each selenese request, remove the first comand from the "out" queue
 * and push the results into the "in" queque (also in session). When "out" queque
 * is empty, write the results to disk.
 *
 * Usage: See ../../example.php
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the BSD License.
 *
 * Copyright(c) 2004 by Wei Zhuo. All rights reserved.
 *
 * To contact the author write to {@link mailto:weizhuo[at]gmail[dot]com Wei Zhuo}
 * The latest version of PRADO can be obtained from:
 * {@link http://prado.sourceforge.net/}
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id: selenium.php 1771 2007-03-26 00:27:59Z xue $
 * @package Prado.tests
 */

/**
 * Selenium automatic client runner,
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Id: selenium.php 1771 2007-03-26 00:27:59Z xue $
 * @package Prado.tests
 */

require_once(dirname(__FILE__).'/results.php');

class SeleniumTestRunner
{
	protected $driver;
	protected $base_dir = '';

	public function __construct($driver=null, $base_dir='../javascript/')
	{
		if(is_null($driver) && !(php_sapi_name() == 'cli'))
			$driver = $_SERVER['SCRIPT_NAME'];
		$this->driver = $driver;
		$this->base_dir = $base_dir;
	}

	public function render()
	{
		if((php_sapi_name() == 'cli')) return;
		$file = dirname(__FILE__).'/TestRunner.php';
		$driver = $this->driver;

		//$base_dir = $this->base_dir;
		$base_dir = $driver.'?sr=';
		include($file);
	}

	public function getDriver()
	{
		return $this->driver;
	}
}

class SeleniumTestStorage
{
	protected $outputs = array();
	protected $tests = array();
	protected $options=array();

	public function getTests()
	{
		return $this->tests;
	}

	public function getOptions()
	{
		return $this->options;
	}

	public function addOption($test_name, $option)
	{
		$this->options[$test_name] = $option;
	}

	public function addCommand($test_case_id, $command)
	{
		$data = array($test_case_id, $command);
		array_push($this->outputs, $data);
	}

	public function getCommand()
	{
		return array_shift($this->outputs);
	}

	public function addTestCase($command, $trace_details, $test_name, $test_suite)
	{
		$data = array(0, 0, $command, "", $trace_details, $test_name, $test_suite);
		array_push($this->tests, $data);
	}
}

class SeleneseInterpreter
{
	protected $storage;
	protected $tracer;

	public function __construct($storage, $tracer)
	{
		$this->storage = $storage;
		$this->tracer = $tracer;
	}

	public function getTests()
	{
		return $this->storage->getTests();
	}

	public function getOptions()
	{
		return $this->storage->getOptions();
	}

	public function getCommand()
	{
		$command = $this->storage->getCommand();
		return empty($command) ? "|testComplete|||" : "{$command[1]}<{$command[0]}>";
	}

	public function __call($func, $args)
	{
		if($func{0} == '_') return;

		$trace = debug_backtrace();
		if($this->isTestOptionFunction($func,$args,$trace))
			return;

		$ID = isset($args[0]) ? $args[0] : "";
		$value = isset($args[1]) ? $args[1] : "";
		if(strpos(strtolower($func),'htmlpresent') || strpos(strtolower($func),'htmlnotpresent'))
			$ID = htmlspecialchars($ID);
		$command = array($func, $ID, $value);

		if(is_int(strpos(strtolower($func), 'visible')))
			$this->addCommand(array('pause','250',''),$trace);

		return $this->addCommand($command, $trace);
	}

	protected function isTestOptionFunction($func,$args,$trace)
	{
		if(strtolower($func)==='skipcondition')
		{
			list($trace, $test, $suite) = $this->tracer->getTrace($trace);
			$this->storage->addOption($test,$args[0]);
			return true;
		}
		return false;
	}

	protected function addCommand($command, $trace)
	{
		list($trace, $test, $suite) = $this->tracer->getTrace($trace);
		$test_id = $this->storage->addTestCase($command, $trace, $test, $suite);
		$this->storage->addCommand($test_id, $command);
	}
}

class SeleniumTestTrace
{
	protected $root;

	public function __construct($root)
	{
		$this->root = $root;
	}

	public function getTrace($trace)
	{
		$group = array_pop($trace);
		$info = $trace[3];
		$test = $group['args'][0]->getTestList();
		$i = count($test);
		$name = $test[$i-2].'::'.$test[$i-1];
		$suite = $test[0];
		unset($info['object']);
		/*
		for($i = 0; $i < count($info['args']); $i++)
		{
			if($info['args'][$i] instanceof TControl)
				$info['args'][$i] = $info['args'][$i]->ClientID;
		}*/
		$file = str_replace($this->root, '', $info['file']);
		$info['file'] = substr($file, 1);
 		return array($info, $name, $suite);
	}
}

class SimpleSeleniumProxyServer
{
	protected $runner;
	protected $int;
	protected $result_file;

	public function __construct($runner, $int, $result_file)
	{
		$this->int = $int;
		$this->runner = $runner;
		$this->result_file = $result_file;
	}

	public function proxy()
	{
		return $this->int;
	}


	public static function getInstance($root='/', $result_file='results.dat', $base_dir='selenium/')
	{
		static $instance;
		if(!isset($instance))
		{
			$storage = new SeleniumTestStorage();
			$tracer = new SeleniumTestTrace($root);
			$interpreter = new SeleneseInterpreter($storage, $tracer);
			$runner = new SeleniumTestRunner(null, $base_dir);
			$instance = new SimpleSeleniumProxyServer($runner, $interpreter, $result_file);
		}
		$instance->serveResults();
		return $instance;
	}

	public function handleRequest()
	{
		$client = new SeleniumTestRunnerServer($this->int, $this->runner);
		$client->serve();
		return true;
	}

	public function serveResults()
	{
		if(isset($_POST['result']))
		{
			$result = new SeleniumTestResult();
			$reporter = new SeleniumHtmlReporter($result);
			$reporter->render();
			exit();
		}
	}

}

class SeleniumTestSuiteWriter
{
	protected $suites;
	protected $name;
	protected $runner;

	function __construct($suites, $name, $runner)
	{
		$this->suites = $suites;
		$this->name = $name;
		$this->runner = $runner;

	}

	protected function renderHeader()
	{
		$base_dir = $this->runner->getDriver().'?sr=';

		include(dirname(__FILE__).'/TestSuiteHeader.php');

		$contents = <<<EOD
            <tr><td><b>{$this->name}</b></td></tr>
EOD;
		echo $contents;
	}

	public function render()
	{
		$this->renderHeader();
		foreach($this->suites as $name => $suite)
		{
			$file = $suite[0]['trace']['file'];
			$file = strtr($file,'\\','/');
			$option = $suite[0]['option']===null?'':' unless="'.$suite[0]['option'].'" ';
			$url = $this->runner->getDriver()."?case={$name}&file={$file}";
			echo "<tr{$option}>\n";
            echo "<td><a href=\"{$url}\">{$name}</a></td>\n";
            echo "</tr>\n";
		}
		echo $this->renderFooter();
	}

	protected function getJsTraceInfo()
	{
		$contents = "var prado_trace = {};\n";
		foreach($this->suites as $name => $suite)
		{
			$name = $name;
			$contents .= "prado_trace['{$name}'] = [";
			$cases = array();
			foreach($suite as $testcase)
				$cases[] = "'".addslashes(htmlspecialchars(serialize($testcase['trace'])))."'";
			$contents .= implode(",\n", $cases)."];\n\n";
		}
		return $contents;
	}

	protected function renderFooter()
	{
		$trace = '';//$this->getJsTraceInfo();
		$contents = <<<EOD
        </tbody>
    </table>

    <br />
    <em>Not supported in this browser</em>
    <table id="skippedTests" cellpadding="1"
           cellspacing="1"
           border="1"
           class="selenium">
        <tbody>
            <tr><td><b>Skipped Tests</b></td></tr>
        </tbody>
    </table>
	<script type="text/javascript">
	/*<![CDATA[*/
		$trace
	/*]]>*/
	</script>
</body>
</html>
EOD;
		return $contents;
	}
}

class SeleniumTestCaseWriter
{
	protected $case;
	protected $tests;

	function __construct($case, $tests)
	{
		$this->case = $case;
		$this->tests = $tests;
	}

	protected function renderHeader()
	{
		$contents = <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>{$this->case}</title>
  <meta content="text/html; charset=UTF-8" http-equiv="content-type">
</head>
<body>
<table cellpadding="1" cellspacing="1" border="1" id=TABLE1>
  <tbody>
    <tr>
      <td rowspan="1" colspan="3"><strong>{$this->case}</strong></td>
    </tr>
EOD;
		return $contents;
	}

	public function render()
	{
		echo $this->renderHeader();
		foreach($this->tests as $test)
		{
			$t = $test['test'];
			if($t[0] == "open")
				$t[1] = "<a href=\"{$t[1]}\" target=\"_blank\">{$t[1]}</a>";
			echo "<tr>\n";
			echo "<td>{$t[0]}</td>\n";
			echo "<td>{$t[1]}</td>\n";
			echo "<td>{$t[2]}</td>\n";
			echo "</tr>\n";
		}
		echo $this->renderFooter();
	}

	protected function renderFooter()
	{
		$contents = <<<EOD
  </tbody>
</table>
</body>
</html>
EOD;
		return $contents;
	}
}

class SeleniumTestRunnerServer
{
	protected $cases = array();
	protected $trace = array();
	protected $name;
	protected $runner;

	public function __construct($int, $runner)
	{
		$this->runner = $runner;
		$this->initialize($int);
	}

	protected function initialize($int)
	{
		$options = $int->getOptions();
		foreach($int->getTests() as $command)
		{
			$case = $command[5];
			$option=isset($options[$case])?$options[$case]:null;
			$this->cases[$case][] =
				array('test' => $command[2],
					'trace' => $command[4], 'option'=>$option);
			if(is_null($this->name))
				$this->name = $command[6];
		}
	}

	function serve()
	{
		if($this->isTestSuiteRequest())
		{
			$testSuite = new SeleniumTestSuiteWriter($this->cases,
								$this->name, $this->runner);
			$testSuite->render();
		}
		else if($this->isTestCaseRequest())
		{
			if(($case = $this->getTestCaseRequest()) !== false)
			{

				$testCase = new SeleniumTestCaseWriter($case, $this->getTestCase());
				$testCase->render();
			}
		}
		else
		{
			$this->runner->render();
		}
	}

	protected function isTestSuiteRequest()
	{
		return isset($_GET['testSuites']);
	}

	protected function isTestCaseRequest()
	{
		return isset($_GET['case']);
	}

	public function isClientRequest()
	{
		return !$this->isTestSuiteRequest() && !$this->isTestCaseRequest();
	}

	protected function getTestCaseRequest()
	{
		$case = $_GET['case'];
		if(isset($this->cases[$case]))
			return $case;
		else return false;
	}

	protected function getTestCase()
	{
		$case = $_GET['case'];
		if(isset($this->cases[$case]))
			return $this->cases[$case];
		else
			return array();
	}
}


class SeleniumTestCase extends UnitTestCase
{
	protected $selenium;
	protected $Page;

	const KONQUEROR='browserVersion.isKonqueror';
	const OPERA='browserVersion.isOpera';
	const CHROME='browserVersion.isChrome';
	const INTERNET_EXPLORER='browserVersion.isIE';
	const SAFARI='browserVersion.isSafari';
	const KHTML='browserVersion.khtml';
	const FIREFOX='browserVersion.isFirefox';
	const MOZILLA='browserVersion.isMozilla';
	const GECKO='browserVersion.isGecko';

	protected $options=array();

	function __construct()
	{
		$server = SimpleSeleniumProxyServer::getInstance();
		if(!is_null($server))
			$this->selenium = $server->proxy();
		parent::__construct();
	}

	function getPage($class)
	{
		$info = new ReflectionClass($class);
		return Prado::getApplication()->getTestPage($info->getFileName());
	}

	function __call($func, $args)
	{
		if(count($args) == 0)
			return $this->selenium->{$func}();
		else if	(count($args) == 1)
			return $this->selenium->{$func}($args[0]);
		else if (count($args) == 2)
			return $this->selenium->{$func}($args[0], $args[1]);
	}

	function disabled()
	{
		$this->selenium->skipCondition('DISABLED');
	}

	function skipBrowsers()
	{
		$conditions = $this->getBrowserOptions(func_get_args());
		$this->selenium->skipCondition($conditions);
	}

	protected function getBrowserOptions($arg_list)
	{
		$browsers=array();
		foreach($arg_list as $arg)
		{
			if(is_array($arg))
				$browsers[] = '('.implode(' && ', $arg).')';
			else
				$browsers[] = $arg;
		}
		return implode(' || ', $browsers);
	}

	function targetBrowsers()
	{
		$conditions = $this->getBrowserOptions(func_get_args());
		$this->selenium->skipCondition("!(".$conditions.")");
	}
}

?>