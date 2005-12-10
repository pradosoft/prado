<?php

require('config.php');

class BrowserTestConfig extends PradoTestConfig
{
	//functional test groups
	public function unit_test_groups()
	{
		$groups['Web/UI'] = realpath($this->tests_directory().'/Web/UI/');
		$groups['Demos'] = realpath($this->tests_directory().'/Demos/');
		return $groups;
	}
}


$root = dirname(__FILE__);
$server = SimpleSeleniumProxyServer::getInstance($root);

$tester = new PradoSimpleTester(new BrowserTestConfig());
$browser_tests = $tester->getTests();
$browser_tests->run(new SeleniumReporter());

$server->handleRequest();

?>