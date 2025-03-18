<?php

namespace Prado\Tests;

use PHPUnit\Event;
use PHPUnit\Runner;
use PHPUnit\TextUI;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Firefox\FirefoxOptions;

/* These classes register to phpunit events in order to setup
 * and tear down the Webdriver when needed. This approach avoids
 * the driver being destroyed/recreated for every single test.
 */

class TestSuiteStarted implements Event\TestSuite\StartedSubscriber
{
    public function notify(Event\TestSuite\Started $event): void
    {
    	if($event->testSuite()->name() === 'functional') {
    		PradoGenericSelenium2TestSession::startDriver();
    	}
    }
}

class TestSuiteFinished implements Event\TestSuite\FinishedSubscriber
{
    public function notify(Event\TestSuite\Finished $event): void
    {
    	if($event->testSuite()->name() === 'functional') {
    		PradoGenericSelenium2TestSession::stopDriver();
    	}
    }
}

class PradoGenericSelenium2TestSession
{
	public static $serverUrl = 'http://localhost:4444';
	static $driver;

	public static function startDriver(): void
	{
		/*
		$chromeOptions = new ChromeOptions();
		$chromeOptions->addArguments(['--headless']);
		$capabilities = DesiredCapabilities::chrome();
		$capabilities->setCapability(ChromeOptions::CAPABILITY_W3C, $chromeOptions);
		*/

		$firefoxOptions = new FirefoxOptions();
		$firefoxOptions->addArguments(['-headless']);
		$capabilities = DesiredCapabilities::firefox();
		$capabilities->setCapability(FirefoxOptions::CAPABILITY, $firefoxOptions);

		self::$driver = RemoteWebDriver::create(self::$serverUrl, $capabilities);
	}

	public static function stopDriver(): void
	{
		self::$driver->quit();
	}

	public static function getDriver()
	{
		return self::$driver;
	}
}

class PradoTestListener implements Runner\Extension\Extension
{
	public function bootstrap(
		TextUI\Configuration\Configuration $configuration,
		Runner\Extension\Facade $facade,
		Runner\Extension\ParameterCollection $parameters
	): void {
		$facade->registerSubscribers(
			new TestSuiteStarted(),
			new TestSuiteFinished()
		);
	}
}