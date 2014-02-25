<?php
require_once 'PHPUnit/Extensions/Selenium2TestCase.php';

// TODO: stub
class PradoGenericSelenium2Test extends PHPUnit_Extensions_Selenium2TestCase
{
	public static $browsers = array(
/*
		array(
			'name'    => 'Firefox on OSX',
			'browserName' => '*firefox',
			'host'    => '127.0.0.1',
			'port'    => 4444,
			'timeout' => 30000,
		),
*/
		array(
			'name'    => 'Chrome on OSX',
			'browserName' => '*googlechrome',
			'host'    => '127.0.0.1',
			'port'    => 4444,
			'timeout' => 30000,
		),
/*
		array(
			'name'    => 'Firefox on WindowsXP',
			'browserName' => '*firefox',
			'host'    => '127.0.0.1',
			'port'    => 4445,
		),
		array(
			'name'    => 'Internet Explorer 8 on WindowsXP',
			'browserName' => '*iehta',
			'host'    => '127.0.0.1',
			'port'    => 4445,
		)
*/
	);

	static $baseurl='http://192.168.44.82/prado-master/tests/FunctionalTests/';

	static $timeout=5; //seconds
	static $wait=1000; //msecs

	protected function setUp()
	{
		$this->setBrowserUrl(static::$baseurl);
		$this->setSeleniumServerRequestsTimeout(static::$timeout);
	}

	public function setUpPage()
	{
		$this->timeouts()->implicitWait(static::$wait);
	}

	protected function open($url)
	{
		$this->url($url);
	}

	protected function tearDown()
	{
	}

	protected function verifyTextPresent($txt)
	{
		$this->assertContains($txt, $this->source());
	}

	protected function assertText($id, $txt)
	{
        $element = $this->byId($id);
        $this->assertEquals($txt, $element->text());
	}

	protected function pause($msec)
	{
		usleep($msec*1000);
	}

}