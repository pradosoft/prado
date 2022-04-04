<?php

class PradoGenericSelenium2Test extends \PHPUnit\Extensions\Selenium2TestCase
{
	public static $browsers = [
/*
		array(
			'name'    => 'Firefox on OSX',
			'browserName' => '*firefox',
			'host'    => '127.0.0.1',
			'port'    => 4444,
		),
*/
		[
			'name' => 'Chrome on OSX',
			'browserName' => 'chrome',
			'sessionStrategy' => 'shared',
			'host' => '127.0.0.1',
			'port' => 4444,
		],
/*
		array(
			'name'    => 'Safari on OSX',
			'browserName' => 'safari',
			'sessionStrategy' => 'shared',
			'host'    => '127.0.0.1',
			'port'    => 4444,
		),
*/
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
	];

	public static $baseurl = 'http://127.0.0.1/prado-master/tests/FunctionalTests/';

	public static $timeout = 5; //seconds

	protected function setUp(): void
	{

		// Workaround for https://github.com/giorgiosironi/phpunit-selenium/issues/436
		$this->setDesiredCapabilities([
			'goog:chromeOptions' => [
				'w3c' => false,
				// 'args' => ['headless'],
			],
		]);
		self::shareSession(true);
		$this->setBrowserUrl(static::$baseurl);
		$this->setSeleniumServerRequestsTimeout(static::$timeout);
	}

	protected function url($t)
	{
		parent::url(static::$baseurl . $t);
	}

	protected function assertAttribute($idattr, $txt)
	{
		[$id, $attr] = explode('@', $idattr);

		$element = $this->getElement($id);
		$value = $element->attribute($attr);

		if (strpos($txt, 'regexp:') === 0) {
			$this->assertMatchesRegularExpression('/' . substr($txt, 7) . '/', $value);
		} else {
			$this->assertEquals($txt, $value);
		}
	}

	protected function getElement($id)
	{
		if (strpos($id, 'id=') === 0) {
			return $this->byId(substr($id, 3));
		} elseif (strpos($id, 'name=') === 0) {
			return $this->byName(substr($id, 5));
		} elseif (strpos($id, '//') === 0) {
			return $this->byXPath($id);
		} elseif (strpos($id, '$') !== false) {
			return $this->byName($id);
		} else {
			return $this->byId($id);
		}
	}

	protected function assertText($id, $txt)
	{
		$this->assertEquals($txt, $this->getElement($id)->text());
	}

	protected function assertValue($id, $txt)
	{
		try {
			$this->assertEquals($txt, $this->getElement($id)->value());
		} catch (\PHPUnit\Extensions\Selenium2TestCase\WebDriverException $e) {
			//stale element reference. try second time.
			$this->pause(50);
			$this->assertEquals($txt, $this->getElement($id)->value());
		}
	}

	protected function assertVisible($id)
	{
		try {
			$this->assertTrue($this->getElement($id)->displayed());
		} catch (\PHPUnit\Extensions\Selenium2TestCase\WebDriverException $e) {
			//stale element reference. try second time.
			$this->pause(50);
			$this->assertTrue($this->getElement($id)->displayed());
		}
	}

	protected function assertNotVisible($id)
	{
		try {
			$this->assertFalse($this->getElement($id)->displayed());
		} catch (\PHPUnit\Extensions\Selenium2TestCase\WebDriverException $e) {
			//stale element reference. try second time.
			$this->pause(50);
			$this->assertFalse($this->getElement($id)->displayed());
		}
	}

	protected function assertElementPresent($id)
	{
		$this->assertTrue($this->getElement($id) !== null);
	}

	protected function assertElementNotPresent($id)
	{
		try {
			$el = $this->getElement($id);
		} catch (\PHPUnit\Extensions\Selenium2TestCase\WebDriverException $e) {
			$this->assertEquals(\PHPUnit\Extensions\Selenium2TestCase\WebDriverException::NoSuchElement, $e->getCode());
			return;
		} catch (\InvalidArgumentException $e) {
			$this->assertEquals('Element not found.', $e->getMessage());
			return;
		}
		$this->fail('The element ' . $id . ' shouldn\'t exist.');
	}

	protected function type($id, $txt = '')
	{
		$element = $this->getElement($id);
		$element->clear();
		$element->value($txt);
		// trigger onblur() event
		$this->byCssSelector('body')->click();
	}

	protected function typeSpecial($id, $txt = '')
	{
		$element = $this->getElement($id);
		// clear the textbox without using clear() that triggers onchange()
		// the idea is to focus the input, move to the end of the text and hit
		// backspace until the input is empty.
		// on multiline textareas, line feeds can make this difficult, so we mix
		// sequences of end+backspace and start+delete

		$element->click();
		while (strlen($element->value()) > 0) {
			$this->keys(\PHPUnit\Extensions\Selenium2TestCase\Keys::END);
			// the number 100 is purely empiric
			for ($i = 0;$i < 100;$i++) {
				$this->keys(\PHPUnit\Extensions\Selenium2TestCase\Keys::BACKSPACE);
			}

			$this->keys(\PHPUnit\Extensions\Selenium2TestCase\Keys::HOME);
			// the number 100 is purely empiric
			for ($i = 0;$i < 100;$i++) {
				$this->keys(\PHPUnit\Extensions\Selenium2TestCase\Keys::DELETE);
			}
		}

		$element->value($txt);
		// trigger onblur() event
		$this->keys(\PHPUnit\Extensions\Selenium2TestCase\Keys::TAB);
	}

	protected function select($id, $value)
	{
		$select = parent::select($this->getElement($id));
		$select->clearSelectedOptions();

		$select->selectOptionByLabel($value);
	}

	protected function selectAndWait($id, $value)
	{
		$this->select($id, $value);
	}

	protected function addSelection($id, $value)
	{
		parent::select($this->getElement($id))->selectOptionByLabel($value);
	}

	protected function getSelectedLabels($id)
	{
		return parent::select($this->getElement($id))->selectedLabels();
	}

	protected function getSelectOptions($id)
	{
		return parent::select($this->getElement($id))->selectOptionLabels();
	}

	protected function assertSelectedIndex($id, $value)
	{
		$options = parent::select($this->getElement($id))->selectOptionValues();
		$curval = parent::select($this->getElement($id))->selectedValue();

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
		$this->assertSame($label, parent::select($this->getElement($id))->selectedLabel());
	}

	protected function assertNotSomethingSelected($id)
	{
		$this->assertSame([], $this->getSelectedLabels($id));
	}

	protected function assertSelectedValue($id, $index)
	{
		$this->assertSame($index, parent::select($this->getElement($id))->selectedValue());
	}

	protected function assertAlertNotPresent()
	{
		try {
			$foo = $this->alertText();
		} catch (\PHPUnit\Extensions\Selenium2TestCase\WebDriverException $e) {
			$this->assertEquals(\PHPUnit\Extensions\Selenium2TestCase\WebDriverException::NoAlertOpenError, $e->getCode());
			return;
		}
		$this->fail('Failed asserting no alert is open');
	}

	protected function pause($msec)
	{
		usleep($msec * 1000);
	}

	protected function pauseFairAmount()
	{
		usleep(100000);
	}

	protected function assertSourceContains($text)
	{
		$found = strpos($this->source(), $text) !== false;
		for ($i = 0;$i < 10 && ! $found; $i++) {
			$this->pause(20);
			$found = strpos($this->source(), $text) !== false;
		}
		$this->assertTrue($found, "Failed asserting that page source contains $text");
	}

	protected function assertSourceNotContains($text)
	{
		$found = strpos($this->source(), $text) !== false;
		for ($i = 0;$i < 10 && $found; $i++) {
			$this->pause(20);
			$found = strpos($this->source(), $text) !== false;
		}
		$this->assertFalse($found, "Failed asserting that page source does not contain $text");
	}
}

class PradoDemosSelenium2Test extends PradoGenericSelenium2Test
{
	public static $baseurl = 'http://127.0.0.1/prado-demos/';
}
