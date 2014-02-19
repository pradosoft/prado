<?php
require_once 'PHPUnit/Extensions/Selenium2TestCase.php';

// TODO: stub
class PradoGenericSelenium2Test extends PHPUnit_Extensions_Selenium2TestCase
{
	static $browser='firefox';
	static $baseurl='http://127.0.0.1/prado-master/tests/FunctionalTests/';
	static $timeout=5; //seconds
	static $wait=1000; //msecs

	protected function setUp()
	{
		$this->setBrowser(static::$browser);
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