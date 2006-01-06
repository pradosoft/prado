<?php

require('config.php');

class BrowserTestConfig extends PradoTestConfig
{
	//functional test groups
	public function unit_test_groups()
	{
		$groups = array();
		$this->get_directories($this->tests_directory(),$groups);
		return $groups;
	}

	protected function get_directories($base,&$groups)
	{
		$groups[] = realpath($base);
		$dirs = new DirectoryIterator($base);
		foreach($dirs as $dir)
			if(!$dir->isDot() && $dir->isDir())
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