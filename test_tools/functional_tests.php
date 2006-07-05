<?php

$TEST_TOOLS = dirname(__FILE__);

if(isset($_GET['sr']))
{
	
	if(($selenium_resource=realpath($TEST_TOOLS.'/selenium/'.$_GET['sr']))!==false)
		echo file_get_contents($selenium_resource);
	exit;
}

require_once($TEST_TOOLS.'/simpletest/unit_tester.php');
require_once($TEST_TOOLS.'/simpletest/web_tester.php');
require_once($TEST_TOOLS.'/simpletest/mock_objects.php');
require_once($TEST_TOOLS.'/simpletest/reporter.php');
require_once($TEST_TOOLS.'/selenium/php/selenium.php');

class PradoFunctionalTester
{
	private $_name;
	private $_basePath;
	private $_selenium;

	public function __construct($basePath,$selenium='',$name='All Tests')
	{
		$this->_name=$name;
		if($basePath==='' || ($this->_basePath=realpath($basePath))===false)
			throw new Exception('Invalid base path '.$basePath);
		$this->_basePath=strtr($this->_basePath,'\\','/');
				
		$this->_selenium = $selenium.'selenium/';
	}

	public function run($simpleReporter)
	{
		$server=SimpleSeleniumProxyServer::getInstance(dirname(__FILE__));//, '', $this->_selenium);

		$groupTest=new GroupTest($this->_name);
		$this->collectTestFiles($groupTest,$this->_basePath);
		$groupTest->run($simpleReporter);

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