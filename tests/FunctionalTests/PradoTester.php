<?php

$SIMPLE_TEST = dirname(__FILE__).'/../UnitTests';

require_once($SIMPLE_TEST.'/simpletest/unit_tester.php');
require_once($SIMPLE_TEST.'/simpletest/web_tester.php');
require_once($SIMPLE_TEST.'/simpletest/mock_objects.php');
require_once($SIMPLE_TEST.'/simpletest/reporter.php');
require(dirname(__FILE__).'/selenium/php/selenium.php');

class PradoTester
{
	private $_name;
	private $_basePath;

	public function __construct($basePath,$name='All Tests')
	{
		$this->_name=$name;
		if($basePath==='' || ($this->_basePath=realpath($basePath))===false)
			throw new Exception('Invalid base path '.$basePath);
		$this->_basePath=strtr($this->_basePath,'\\','/');
	}

	public function run($simpleReporter)
	{
		$groupTest=new GroupTest($this->_name);
		$this->collectTestFiles($groupTest,$this->_basePath);
		$groupTest->run($simpleReporter);

		$server=SimpleSeleniumProxyServer::getInstance(dirname(__FILE__));
		$server->handleRequest();
	}

	protected function collectTestFiles($groupTest,$basePath)
	{
		$folder=@opendir($basePath);
		while($entry=@readdir($folder))
		{
			$fullPath=strtr($basePath.'/'.$entry,'\\','/');
			if(is_file($fullPath) && $this->isValidFile($entry))
				$groupTest->addTestFile($fullPath);
			else if($entry[0]!=='.')
				$this->collectTestFiles($groupTest,$fullPath);
		}
		@closedir($folder);
	}

	protected function isValidFile($entry)
	{
		return preg_match('/\w+\.php$/',$entry);
	}
}

?>