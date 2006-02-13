<?php

require('config.php');
header("Content-Type: text/html; charset=UTF-8");
class BrowserTestConfig extends PradoTestConfig
{
	//functional test groups
	public function unit_test_groups()
	{
		$groups = array();

		//tests for quickstart samples
		$this->get_directories(dirname(__FILE__).'/quickstart', $groups);

		return $groups;
	}

	protected function get_directories($base,&$groups)
	{
		$groups[] = realpath($base);
		$dirs = new DirectoryIterator($base);
		foreach($dirs as $dir)
			if(!$dir->isDot() && $dir->isDir()
				&& !preg_match("/\.svn/", $dir->getPathName()))
				$this->get_directories($dir->getPathName(), $groups);
	}
}


$root = dirname(__FILE__);
$server = SimpleSeleniumProxyServer::getInstance($root);

$tester = new PradoSimpleTester(new BrowserTestConfig());
$browser_tests = $tester->getTests();
$browser_tests->run(new SimpleReporter());

$server->handleRequest();

?>