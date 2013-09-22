<?php
require_once 'PHPUnit/Extensions/SeleniumTestCase.php';
 
class PradoGenericSeleniumTest extends PHPUnit_Extensions_SeleniumTestCase
{
	protected function setUp()
	{
		$this->setBrowser('*googlechrome');
		$this->setBrowserUrl('http://127.0.0.1/prado-3.2/tests/FunctionalTests/');
	}

	protected function tearDown()
	{
	}
}