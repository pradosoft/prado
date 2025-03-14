<?php

namespace Prado\Tests;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\NoAlertOpenException;
use Facebook\WebDriver\Exception\NoSuchAlertException;

class PradoGenericSelenium2Test extends \PHPUnit\Framework\TestCase
{
	public static $baseurl = 'http://127.0.0.1/prado-master/tests/FunctionalTests/';
	public static $driver;
	protected static $timeout = 5; // secs
	protected static $polltime = 200; // msecs

	public static function setUpBeforeClass(): void
	{
		self::$driver = \Prado\Tests\PradoGenericSelenium2TestSession::getDriver();
	}

	protected function url($t)
	{
		self::$driver->get(static::$baseurl . $t);
	}

	protected function refresh()
	{
		self::$driver->navigate()->refresh();
	}

	protected static function waitForAjaxCalls()
	{
		self::$driver->wait(self::$timeout, self::$polltime)->until(function () {
			return !self::$driver->executeScript('return typeof jQuery === "undefined" || jQuery.active');
		});
	}

	protected function getElement($id)
	{
		if (strpos($id, 'id=') === 0) {
			return $this->byId(substr($id, 3));
		} elseif (strpos($id, 'name=') === 0) {
			return $this->byName(substr($id, 5));
		} elseif (strpos($id, '//') === 0) {
			return $this->byXpath($id);
		} elseif (strpos($id, '$') !== false) {
			return  $this->byName($id);
		} else {
			return $this->byId($id);
		}
	}

	private static function WebDriverBy($id)
	{
		if (strpos($id, 'id=') === 0) {
			return WebDriverBy::id(substr($id, 3));
		} elseif (strpos($id, 'name=') === 0) {
			return WebDriverBy::name(substr($id, 5));
		} elseif (strpos($id, '//') === 0) {
			return WebDriverBy::xpath($id);
		} elseif (strpos($id, '$') !== false) {
			return WebDriverBy::name($id);
		} else {
			return WebDriverBy::id($id);
		}
	}

	protected function byId($id)
	{
		return self::$driver->wait(self::$timeout, self::$polltime)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::id($id))
		);
	}

	protected function byName($name)
	{
		return self::$driver->wait(self::$timeout, self::$polltime)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::name($name))
		);
	}

	protected function byCssSelector($css)
	{
		return self::$driver->wait(self::$timeout, self::$polltime)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::cssSelector($css))
		);
	}

	protected function byXPath($xpath)
	{
		return self::$driver->wait(self::$timeout, self::$polltime)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::xpath($xpath))
		);
	}

	protected function byLinkText($text)
	{
		return self::$driver->wait(self::$timeout, self::$polltime)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::linkText($text))
		);
	}

	protected function source()
	{
		return self::$driver->getPageSource();
	}

	protected function assertTitle($title)
	{
		$this->assertTrue(self::$driver->wait(self::$timeout, self::$polltime)->until(
			WebDriverExpectedCondition::titleIs($title)
		));
	}

	protected function moveto($element)
	{
		self::$driver->getMouse()->mouseMove($element->getCoordinates());
	}

	protected function keys($keys)
	{
		self::$driver->getKeyboard()->sendKeys($keys);
	}

	protected function assertText($id, $txt)
	{
		$this->assertTrue(self::$driver->wait(self::$timeout, self::$polltime)->until(
			WebDriverExpectedCondition::elementTextIs(self::WebDriverBy($id), $txt)
		));
	}

	protected function assertValue($id, $txt)
	{
		$this->assertTrue(self::$driver->wait(self::$timeout, self::$polltime)->until(
			WebDriverExpectedCondition::textToBePresentInElementValue(self::WebDriverBy($id), $txt)
		));
	}

	protected function assertAttribute($idattr, $txt)
	{
		[$id, $attr] = explode('@', $idattr);

		$this->assertTrue(self::$driver->wait(self::$timeout, self::$polltime)->until(
			function () use ($id, $attr, $txt) {
				$element = $this->getElement($id);
				$value = $element->getAttribute($attr);

				if (strpos($txt, 'regexp:') === 0) {
					return preg_match('/' . substr($txt, 7) . '/', $value) > 0;
				} else {
					return $txt === $value;
				}
			}
		));
	}

	protected function assertVisible($id)
	{
		$this->assertIsObject(self::$driver->wait(self::$timeout, self::$polltime)->until(
			WebDriverExpectedCondition::visibilityOfElementLocated(self::WebDriverBy($id))
		));
	}

	protected function assertNotVisible($id)
	{
		$this->pause(50);
		$this->assertTrue(self::$driver->wait(self::$timeout, self::$polltime)->until(
			WebDriverExpectedCondition::invisibilityOfElementLocated(self::WebDriverBy($id))
		));
	}

	protected function assertElementPresent($id)
	{
		$this->assertIsObject(self::$driver->wait(self::$timeout, self::$polltime)->until(
			WebDriverExpectedCondition::presenceOfElementLocated(self::WebDriverBy($id))
		));
	}

	protected function assertElementNotPresent($id)
	{
		// wait until element is not visible
		$this->assertTrue(self::$driver->wait(self::$timeout, self::$polltime)->until(
			WebDriverExpectedCondition::invisibilityOfElementLocated(self::WebDriverBy($id))
		));

		// introduce an hardcoded wait to avoid false positives
		$this->pause(50);

		// ensure it's not present
		try {
			$el = self::$driver->findElement(self::WebDriverBy($id));
		} catch (\Facebook\WebDriver\Exception\NoSuchElementException $e) {
			return;
		}

		// fail if the method did not return
		$this->fail('The element ' . $id . ' shouldn\'t exist.');
	}

	protected function click($id)
	{
		$this->pause(50);
		self::$driver->wait(self::$timeout, self::$polltime)->until(
			WebDriverExpectedCondition::elementToBeClickable(self::WebDriverBy($id))
		)->click();
	}

	protected function type($id, $txt = '')
	{
		$this->pause(50);
		$element = $this->getElement($id);
		$element->clear();
		$element->sendKeys($txt);
		// trigger onblur() event: click outside the element (to avoid datepicker popups causing a change to value)
		self::$driver->findElement(WebDriverBy::tagName('body'))->click();
	}

	protected function typeSpecial($id, $txt = '')
	{
		$element = $this->getElement($id);
		$element->sendKeys(WebDriverKeys::END);
		for($i = 0; $i < 100; ++$i) {
			$element->sendKeys(WebDriverKeys::BACKSPACE);
		}

		$element->sendKeys($txt);
		// trigger onblur() event: send tab key
		$element->sendKeys(WebDriverKeys::TAB);
	}

	protected function executeScript($script, $args)
	{
		self::$driver->executeScript($script, $args);
	}

	protected function select($id, $value)
	{
		$select = new WebDriverSelect($this->getElement($id));
		if($select->isMultiple()) {
			$select->deselectAll();
		}

		$select->selectByVisibleText($value);
	}

	protected function addSelection($id, $value)
	{
		$select = new WebDriverSelect($this->getElement($id));
		$select->selectByVisibleText($value);
	}

	protected function getSelectedLabels($id)
	{
		$select = new WebDriverSelect($this->getElement($id));
		$selectedOptions = $select->getAllSelectedOptions();
		return array_map(function($n) {
			return $n->getText();
		}, $selectedOptions);
	}

	protected function getSelectOptions($id)
	{
		$select = new WebDriverSelect($this->getElement($id));
		$options = $select->getOptions();
		return array_map(function($n) {
			return $n->getText();
		}, $options);
	}

	protected function assertSelectedIndex($id, $value)
	{
		$options = $this->getSelectOptions($id);
		$select = new WebDriverSelect($this->getElement($id));
		$curval = $select->getFirstSelectedOption()->getText();

		$i = 0;
		foreach ($options as $option) {
			if ($option == $curval) {
				$this->assertEquals($i, $value);
				return;
			}
			$i++;
		}
		$this->fail('Current value ' . $curval . ' not found in: ' . implode(',', $options));
	}

	protected function assertSelected($id, $label)
	{
		$this->assertTrue(self::$driver->wait(self::$timeout, self::$polltime)->until(
			function () use ($id, $label) {
				$select = new WebDriverSelect($this->getElement($id));
				return $label === $select->getFirstSelectedOption()->getText();
			}
		));
	}

	protected function assertSelectedMultiple($id, $labelsArr)
	{
		$this->assertTrue(self::$driver->wait(self::$timeout, self::$polltime)->until(
			function () use ($id, $labelsArr) {
				$select = new WebDriverSelect($this->getElement($id));
				$selectedOptions = $select->getAllSelectedOptions();
				$selectedLabels = array_map(function($n) {
					return $n->getText();
				}, $selectedOptions);
				return $labelsArr === $selectedLabels;
			}
		));
	}

	protected function assertNotSomethingSelected($id)
	{
		$select = new WebDriverSelect($this->byId($id));
		$this->assertSame([], $select->getAllSelectedOptions());
	}

	protected function assertSelectedValue($id, $value)
	{
		$select = new WebDriverSelect($this->byId($id));
		$this->assertSame($value, $select->getFirstSelectedOption()->getAttribute('value'));
	}

	protected function assertChecked($id)
	{
		$this->assertTrue(self::$driver->wait(self::$timeout, self::$polltime)->until(
			WebDriverExpectedCondition::elementSelectionStateToBe(self::WebDriverBy($id), true)
		));
	}

	protected function assertNotChecked($id)
	{
		$this->assertTrue(self::$driver->wait(self::$timeout, self::$polltime)->until(
			WebDriverExpectedCondition::elementSelectionStateToBe(self::WebDriverBy($id), false)
		));
	}

	protected function alertText()
	{
		return self::$driver->switchTo()->alert()->getText();
	}

	protected function acceptAlert()
	{
		return self::$driver->switchTo()->alert()->accept();
	}

	protected function dismissAlert()
	{
		return self::$driver->switchTo()->alert()->dismiss();
	}

	protected function assertAlertPresent()
	{
		$this->assertTrue(self::$driver->wait(self::$timeout, self::$polltime)->until(
			WebDriverExpectedCondition::alertIsPresent()
		));
	}

	protected function assertAlertNotPresent()
	{
		try {
			$foo = $this->alertText();
		} catch (\Facebook\WebDriver\Exception\NoAlertOpenException $e) {
			return;
		} catch (\Facebook\WebDriver\Exception\NoSuchAlertException $e) {
			return;
		}
		$this->fail('Failed asserting no alert is open');
	}

	protected function active()
	{
		return self::$driver->switchTo()->activeElement();
	}

	protected function pause($msec)
	{
		usleep($msec * 1000);
	}

	protected function assertSourceContains($text)
	{
		$found = self::$driver->wait(self::$timeout, self::$polltime)->until(
			function () use ($text) {
				return strpos(self::$driver->getPageSource(), $text) !== false;
			}
		);
		$this->assertTrue($found, "Failed asserting that page source contains $text");
	}

	protected function assertSourceNotContains($text)
	{
		$notFound = self::$driver->wait(self::$timeout, self::$polltime)->until(
			function () use ($text) {
				return strpos(self::$driver->getPageSource(), $text) === false;
			}
		);
		$this->assertTrue($notFound, "Failed asserting that page source does not contain $text");
	}
}

class PradoDemosSelenium2Test extends \Prado\Tests\PradoGenericSelenium2Test
{
	public static $baseurl = 'http://127.0.0.1/prado-demos/';
}
