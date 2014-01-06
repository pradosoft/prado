<?php
require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

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