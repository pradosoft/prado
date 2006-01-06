<?php

class SeleniumTestCaseResult
{
	public $name;
	public $commands = array();
	public $result = 'passed';
	public $failures = array();
}

class SeleniumTestResult
{
	public $result = 'passed';
	public $totalTime = 0;
	public $numTestPasses = 0;
	public $numTestFailures = 0;
	public $numCommandPasses = 0;
	public $numCommandFailures = 0;
	public $numCommandErrors = 0;
	public $suites = array();
	public $browser = '';
	public $date;

	public function __construct()
	{
		$this->parse_data();
		$this->browser = $_SERVER['HTTP_USER_AGENT'];
		$this->date = time();
	}

	protected function parse_data()
	{
		$this->result = $_POST['result']; // failed || passed
		$this->totalTime = $_POST['totalTime'];
		$this->numTestPasses = $_POST['numTestPasses'];
		$this->numTestFailures = $_POST['numTestFailures'];
		$this->numCommandPasses = $_POST['numCommandPasses'];
		$this->numCommandFailures = $_POST['numCommandFailures'];
		$this->numCommandErrors = $_POST['numCommandErrors'];

		foreach($_POST['tests'] as $test)
		{
			$case = new SeleniumTestCaseResult();
			$case->name = $test['testcase'];
			$case->commands = $test['commands'];
			for($i = 0; $i < count($case->commands); $i++)
			{
				//$trace = $case->commands[$i]['trace'];
				//$trace = html_entity_decode($trace);
				//$case->commands[$i]['trace'] = @unserialize($trace);
				if($case->commands[$i]['result'] == 'failed')
				{
					$case->result = 'failed';
					array_push($case->failures, $case->commands[$i]);
				}
			}

			$this->suites[$case->name] = $case;
		}

	}
}

class SeleniumHtmlReporter
{
	protected $test;

	public function __construct($result)
	{
		$this->test = $result;
	}

	protected function renderHeader()
	{
		$contents = <<<EOD
<html>
<head>
<title>Functional Test Results</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<style type="text/css">
body {font-family:"Verdana";font-weight:normal;color:black;background-color:white;}
.failed { background-color: red; } .error0 { background-color: lightgray; }
.info { padding: 0.5em; margin-top: 1em; color: white; }
.passed { background-color: green; }
.error_case div { padding: 0.3em 0.2em; margin: 0.5em 0; }
.error_msg { padding: 0.5em; border-bottom:1px solid #ccc; }
.msg { color:#c00; }
.function { font-family:"Lucida Console";color: gray;}
.file { border-bottom: 1px dashed gray; }
.env { color: gray; font-size:10pt; padding: 0.5em; }
.odd { background-color: #fee; }
</style>
</head>
<body>
EOD;
		return $contents;
	}

	public function render()
	{
		echo $this->renderHeader();
		echo $this->renderBody();
		echo $this->renderFooter();
	}

	protected function renderBody()
	{
		/* SeleniumTestResult */
		$test = $this->test;
		$total = count($test->suites);
		$date = @strftime('%Y-%m-%d %H:%M',$test->date);
$contents = <<<EOD
<h1 class="suite">Functional Test Results</h1>
<div class="info {$test->result}">
	<strong>{$total}</strong> test cases completed,
	<strong>{$test->numTestPasses}</strong> passes 
	({$test->numCommandPasses} commands), and
	<strong>{$test->numTestFailures}</strong> fails
	({$test->numCommandErrors} commands).
</div>
<div class="env">
	{$date}, {$test->browser}
</div>
EOD;
		$count = 1;
		foreach($test->suites as $suite)
		{
			foreach($suite->failures as $error)
				$contents .= $this->getErrorMsg($suite, $error, $count++);
		}

		return $contents;
	}


	protected function getErrorMsg($suite, $info, $count)
	{
		$parity = $count%2==0 ? 'even' : 'odd';
		$command = explode("|",$info['command']);
$msg = <<<EOD
	<div class="error_msg {$parity}">
		<strong>#{$count}.</strong>
		&quot;<span class="msg">{$info['msg']}</span>&quot; in
		<span class="function">
			{$suite->name}::{$command[1]}('{$command[2]}');
		</span>
	</div>
EOD;

		return $msg;
	}

	protected function renderFooter()
	{
		return "</body></html>";
	}
}


?>