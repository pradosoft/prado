<?php
require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

class PradoGenericSeleniumTest extends PHPUnit_Extensions_SeleniumTestCase
{
	public static $browsers = array(
/*
		array(
			'name'    => 'Firefox on OSX',
			'browser' => '*firefox',
			'host'    => '127.0.0.1',
			'port'    => 4444,
			'timeout' => 30000,
		),
*/
		array(
			'name'    => 'Chrome on OSX',
			'browser' => '*googlechrome',
			'host'    => '127.0.0.1',
			'port'    => 4444,
			'timeout' => 30000,
		),
/*
		array(
			'name'    => 'Firefox on WindowsXP',
			'browser' => '*firefox',
			'host'    => '127.0.0.1',
			'port'    => 4445,
		),
		array(
			'name'    => 'Internet Explorer 8 on WindowsXP',
			'browser' => '*iehta',
			'host'    => '127.0.0.1',
			'port'    => 4445,
		),
*/
	);

	static $baseurl='http://192.168.44.82/prado-master/tests/FunctionalTests/';

	protected function setUp()
	{
		$this->shareSession(true);
		$this->setBrowserUrl(static::$baseurl);
	}

	protected function tearDown()
	{
	}
}