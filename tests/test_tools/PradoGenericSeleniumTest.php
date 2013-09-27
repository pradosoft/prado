<?php
require_once 'PHPUnit/Extensions/SeleniumTestCase.php';
require_once 'PHPUnit/Extensions/Selenium2TestCase.php';
 
class PradoGenericSeleniumTest extends PHPUnit_Extensions_SeleniumTestCase
{
	static $browser='*googlechrome';
	static $baseurl='http://127.0.0.1/prado-3.2/tests/FunctionalTests/';

	protected function setUp()
	{
		$this->shareSession(true);
		$this->setBrowser(static::$browser);
		$this->setBrowserUrl(static::$baseurl);
	}

	protected function tearDown()
	{
	}
}

// TODO: stub
class PradoGenericSelenium2Test extends PHPUnit_Extensions_Selenium2TestCase
{
	static $browser='chrome';
	static $baseurl='http://127.0.0.1/prado-3.2/tests/FunctionalTests/';

	protected function setUp()
	{
		$this->setBrowser(static::$browser);
		$this->setBrowserUrl(static::$baseurl);
	}

	protected function open($url)
	{
		$this->setBrowserUrl(static::$baseurl.$url);
	}

	protected function tearDown()
	{
	}
}