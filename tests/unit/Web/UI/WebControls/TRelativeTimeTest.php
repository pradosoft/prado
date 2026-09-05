<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\Web\Services\TPageService;
use Prado\Web\TAssetManager;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TRelativeTime;
use Prado\Web\UI\WebControls\TRelativeTimeMode;
use Prado\Web\UI\WebControls\TTime;
use PHPUnit\Framework\TestCase;

/**
 * Records whether the client script hook fired, without publishing assets, so the
 * onPreRender branch logic can be tested outside a web-server context.
 */
class TRelativeTimeScriptSpy extends TRelativeTime
{
	public bool $scriptRegistered = false;

	protected function registerClientScript()
	{
		$this->scriptRegistered = true;
	}

	public function exposeClientClassName(): string
	{
		return $this->getClientClassName();
	}
}

class TRelativeTimeTest extends TestCase
{
	use TWebControlRenderTrait;

	private function invokeMethod(object $object, string $method, array $args = []): mixed
	{
		$rm = new \ReflectionMethod($object, $method);
		$rm->setAccessible(true);
		return $rm->invokeArgs($object, $args);
	}

	/**
	 * Builds a TRelativeTime attached to a fresh TPage so getPage()-dependent code works.
	 * @param string $id
	 * @return array{TRelativeTime, TPage}
	 */
	private function makeOnPage(string $id = 'RT'): array
	{
		$page = new TPage();
		$control = new TRelativeTime();
		$control->setID($id);
		$control->setCulture('en_US');
		$page->getControls()->add($control);
		return [$control, $page];
	}

	private function clientOptions(TRelativeTime $control): array
	{
		return $this->invokeMethod($control, 'getClientOptions');
	}

	// ================================================================================
	// Class structure
	// ================================================================================

	public function testExtendsTTime()
	{
		$this->assertInstanceOf(TTime::class, new TRelativeTime());
	}

	public function testRendersTimeTag()
	{
		$control = new TRelativeTime();
		$control->setCulture('en_US');
		$output = $this->render($control);
		$this->assertStringContainsString('<time', $output);
		$this->assertStringContainsString('</time>', $output);
	}

	// ================================================================================
	// Property defaults
	// ================================================================================

	public function testModeDefaultsToLong()
	{
		$this->assertEquals(TRelativeTimeMode::Long, (new TRelativeTime())->getMode());
	}

	public function testDefaults()
	{
		$control = new TRelativeTime();
		$this->assertTrue($control->getClickForDateTime());
		$this->assertTrue($control->getUseServerTime());
		$this->assertTrue($control->getPartialElement());
		$this->assertFalse($control->getDisplayZero());
		$this->assertFalse($control->getDurationOnly());
		$this->assertSame(' ', $control->getSeparator());
		$this->assertSame(1, $control->getSignificantElements());
		$this->assertSame(4, $control->getMinutesWithSeconds());
		$this->assertSame(3, $control->getHoursWithMinutes());
		$this->assertSame(3, $control->getDaysWithHours());
		$this->assertSame(2, $control->getWeeksWithDays());
		$this->assertSame(3, $control->getMonthsWithWeeks());
		$this->assertSame(2, $control->getYearsWithMonths());
	}

	// ================================================================================
	// Mode
	// ================================================================================

	public function testSetMode()
	{
		$control = new TRelativeTime();
		$control->setMode(TRelativeTimeMode::Narrow);
		$this->assertEquals(TRelativeTimeMode::Narrow, $control->getMode());
	}

	public function testSetModeInvalidThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TRelativeTime())->setMode('Bogus');
	}

	public function testSetModeAcceptsAllValues()
	{
		$control = new TRelativeTime();
		foreach ([TRelativeTimeMode::Long, TRelativeTimeMode::Short, TRelativeTimeMode::Narrow] as $mode) {
			$control->setMode($mode);
			$this->assertEquals($mode, $control->getMode());
		}
	}

	// ================================================================================
	// Property coercion
	// ================================================================================

	public function testBooleanCoercion()
	{
		$control = new TRelativeTime();
		$control->setClickForDateTime('false');
		$this->assertFalse($control->getClickForDateTime());
		$control->setDisplayZero('true');
		$this->assertTrue($control->getDisplayZero());
	}

	public function testIntegerCoercion()
	{
		$control = new TRelativeTime();
		$control->setSignificantElements('3');
		$this->assertSame(3, $control->getSignificantElements());
	}

	public function testPartialThresholdRoundTrips()
	{
		$control = new TRelativeTime();
		$control->setMinutesWithSeconds(11);
		$control->setHoursWithMinutes(12);
		$control->setDaysWithHours(13);
		$control->setWeeksWithDays(14);
		$control->setMonthsWithWeeks(15);
		$control->setYearsWithMonths(16);
		$this->assertSame(11, $control->getMinutesWithSeconds());
		$this->assertSame(12, $control->getHoursWithMinutes());
		$this->assertSame(13, $control->getDaysWithHours());
		$this->assertSame(14, $control->getWeeksWithDays());
		$this->assertSame(15, $control->getMonthsWithWeeks());
		$this->assertSame(16, $control->getYearsWithMonths());
	}

	public function testFlagRoundTrips()
	{
		$control = new TRelativeTime();
		$control->setSeparator(' · ');
		$control->setPartialElement(false);
		$control->setUseServerTime(false);
		$control->setClickForDateTime(false);
		$control->setDisplayZero(true);
		$control->setDurationOnly('true');
		$this->assertTrue($control->getDurationOnly());
		$this->assertSame(' · ', $control->getSeparator());
		$this->assertFalse($control->getPartialElement());
		$this->assertFalse($control->getUseServerTime());
		$this->assertFalse($control->getClickForDateTime());
		$this->assertTrue($control->getDisplayZero());
	}

	// ================================================================================
	// Origin resolution
	// ================================================================================

	public function testOriginFromDateTimeInterface()
	{
		$control = new TRelativeTime();
		$dt = new \DateTimeImmutable('2024-06-15 10:30:00');
		$control->setDateTime($dt);
		$this->assertSame($dt->getTimestamp(), $this->invokeMethod($control, 'getOriginTimestamp'));
	}

	public function testOriginFromDateIntervalIsNowMinusInterval()
	{
		$control = new TRelativeTime();
		$control->setDateTime(new \DateInterval('PT1H'));
		$origin = $this->invokeMethod($control, 'getOriginTimestamp');
		$expected = (new \DateTimeImmutable())->getTimestamp() - 3600;
		$this->assertEqualsWithDelta($expected, $origin, 5);
	}

	public function testOriginDefaultsToNowWhenUnset()
	{
		$control = new TRelativeTime();
		$origin = $this->invokeMethod($control, 'getOriginTimestamp');
		$this->assertEqualsWithDelta(time(), $origin, 5);
	}

	// ================================================================================
	// Rendering: no-JS fallback and attributes
	// ================================================================================

	/**
	 * Builds a control whose origin is `$offset` seconds from now. Offsets carry a
	 * half-unit margin so the leading unit stays stable while the test runs.
	 * @param int $offset
	 * @param string $culture
	 */
	private function makeAtOffset(int $offset, string $culture = 'en_US'): TRelativeTime
	{
		$control = new TRelativeTime();
		$control->setCulture($culture);
		$control->setDateTime(time() + $offset);
		return $control;
	}

	public function testRenderContentsIsRelativePast()
	{
		$this->assertSame('5 minutes ago', $this->renderContents($this->makeAtOffset(-330)));
	}

	public function testDurationOnlyDropsPastDirection()
	{
		$control = $this->makeAtOffset(-330);
		$control->setDurationOnly(true);
		$this->assertSame('5 minutes', $this->renderContents($control));
	}

	public function testDurationOnlyDropsFutureDirection()
	{
		$control = $this->makeAtOffset(330);
		$control->setDurationOnly(true);
		$this->assertSame('5 minutes', $this->renderContents($control));
	}

	public function testDurationOnlyMultipleUnits()
	{
		$control = $this->makeAtOffset(-(3600 + 330));
		$control->setSignificantElements(2);
		$control->setDurationOnly(true);
		$this->assertSame('1 hour 5 minutes', $this->renderContents($control));
	}

	public function testDurationOnlyLocalizedUnits()
	{
		$control = $this->makeAtOffset(-330, 'fr');
		$control->setDurationOnly(true);
		$this->assertSame('5 minutes', $this->renderContents($control));
		$control->setMode(TRelativeTimeMode::Narrow);
		$this->assertSame('5min', $this->renderContents($control));
	}

	public function testRenderContentsIsRelativeFuture()
	{
		$this->assertSame('in 5 minutes', $this->renderContents($this->makeAtOffset(330)));
	}

	public function testRenderContentsMultipleUnitsKeepOneDirection()
	{
		$control = $this->makeAtOffset(-(3600 + 330));
		$control->setSignificantElements(2);
		$this->assertSame('1 hour 5 minutes ago', $this->renderContents($control));
	}

	public function testRenderContentsSeparator()
	{
		$control = $this->makeAtOffset(-(3600 + 330));
		$control->setSignificantElements(2);
		$control->setSeparator(', ');
		$this->assertSame('1 hour, 5 minutes ago', $this->renderContents($control));
	}

	public function testRenderContentsPartialElementAddsNextUnit()
	{
		// hours = 2 ≤ HoursWithMinutes (3), so minutes are appended.
		$this->assertSame('2 hours 5 minutes ago', $this->renderContents($this->makeAtOffset(-(2 * 3600 + 330))));
	}

	public function testRenderContentsPartialElementOff()
	{
		$control = $this->makeAtOffset(-(2 * 3600 + 330));
		$control->setPartialElement(false);
		$this->assertSame('2 hours ago', $this->renderContents($control));
	}

	public function testRenderContentsDisplayZero()
	{
		$control = $this->makeAtOffset(-3600);
		$control->setSignificantElements(2);
		$control->setDisplayZero(true);
		$this->assertSame('1 hour 0 minutes ago', $this->renderContents($control));
	}

	public function testRenderContentsZeroDeltaHasNoDirection()
	{
		$this->assertSame('0 seconds', $this->renderContents($this->makeAtOffset(0)));
	}

	public function testRenderContentsShortMode()
	{
		$control = $this->makeAtOffset(-330);
		$control->setMode(TRelativeTimeMode::Short);
		$this->assertSame('5 min. ago', $this->renderContents($control));
	}

	public function testRenderContentsNarrowMode()
	{
		$control = $this->makeAtOffset(-330);
		$control->setMode(TRelativeTimeMode::Narrow);
		$this->assertSame('5m ago', $this->renderContents($control));
	}

	public function testRenderContentsFrench()
	{
		$this->assertSame('il y a 5 minutes', $this->renderContents($this->makeAtOffset(-330, 'fr')));
	}

	public function testRenderContentsRussianSingleUnitInflects()
	{
		// A single unit uses the relative-time pattern directly, carrying the accusative.
		$control = $this->makeAtOffset(-75, 'ru');
		$control->setPartialElement(false);
		$this->assertSame('1 минуту назад', $this->renderContents($control));
	}

	public function testRenderContentsRussianPluralCategories()
	{
		$control = $this->makeAtOffset(-330, 'ru');
		$this->assertSame('5 минут назад', $this->renderContents($control));
	}

	public function testDateTimeAttributeRenderedWhenUnset()
	{
		$control = new TRelativeTime();
		$control->setCulture('en_US');
		$output = $this->render($control);
		$this->assertStringContainsString('datetime="', $output);
	}

	public function testDefaultTitleIsAbsoluteDate()
	{
		$control = new TRelativeTime();
		$control->setCulture('en_US');
		$control->setDateTime(new \DateTimeImmutable('2024-06-15 10:30:00'));
		$output = $this->render($control);
		$this->assertStringContainsString('title="June 15, 2024', $output);
	}

	public function testDeveloperToolTipReplacesDefaultTitle()
	{
		$control = new TRelativeTime();
		$control->setCulture('en_US');
		$control->setDateTime(new \DateTimeImmutable('2024-06-15 10:30:00'));
		$control->setToolTip('Posted');
		$output = $this->render($control);
		$this->assertStringContainsString('title="Posted"', $output);
		$this->assertStringNotContainsString('June', $output);
	}

	public function testDateTimeAttributeReflectsSetValue()
	{
		$control = new TRelativeTime();
		$control->setCulture('en_US');
		$control->setDateTime(new \DateTimeImmutable('2024-06-15 10:30:00'));
		$output = $this->render($control);
		$this->assertStringContainsString('datetime="2024-06-15T10:30:00', $output);
	}

	public function testDateIntervalOriginRendersRelativeToNow()
	{
		$control = new TRelativeTime();
		$control->setCulture('en_US');
		$control->setDateTime(new \DateInterval('PT1H'));
		// Origin resolves to now minus one hour.
		$this->assertSame('1 hour ago', $this->renderContents($control));
	}

	public function testTextFormatOverridesAbsoluteFormat()
	{
		$control = new TRelativeTime();
		$control->setCulture('en_US');
		$control->setDateTime(new \DateTimeImmutable('2024-06-15 10:30:00'));

		// Default (DateTimeFormat = HtmlDateTime) includes the time-of-day.
		$this->assertStringContainsString(':', $this->invokeMethod($control, 'getAbsoluteText'));

		// TextFormat = Date drops the time from the absolute text.
		$control->setTextFormat('Date');
		$dateOnly = $this->invokeMethod($control, 'getAbsoluteText');
		$this->assertStringContainsString('June', $dateOnly);
		$this->assertStringNotContainsString(':', $dateOnly);
	}

	public function testDisabledControlIsNotClientEnhanced()
	{
		// No explicit ID: a disabled control emits neither the auto id nor the client script.
		$page = new TPage();
		$control = new TRelativeTime();
		$control->setCulture('en_US');
		$control->setEnabled(false);
		$page->getControls()->add($control);

		$this->assertFalse($this->invokeMethod($control, 'getClientEnhanced'));
		$this->assertStringNotContainsString('id="', $this->render($control));
	}

	public function testAutoIdRenderedWhenOnPageWithJavaScript()
	{
		// No explicit ID: the client-enhanced path supplies an auto-generated ClientID.
		$page = new TPage();
		$control = new TRelativeTime();
		$control->setCulture('en_US');
		$page->getControls()->add($control);
		$output = $this->render($control);
		$this->assertStringContainsString('id="', $output);
	}

	public function testNoAutoIdWhenClientDoesNotSupportJavaScript()
	{
		// No explicit ID and no JavaScript: no id attribute is emitted.
		$page = new TPage();
		$page->setClientSupportsJavaScript(false);
		$control = new TRelativeTime();
		$control->setCulture('en_US');
		$page->getControls()->add($control);
		$output = $this->render($control);
		$this->assertStringNotContainsString('id="', $output);
	}

	// ================================================================================
	// Client options
	// ================================================================================

	public function testClientOptionsBasics()
	{
		[$control] = $this->makeOnPage('RTopt');
		$control->setDateTime(new \DateTimeImmutable('2024-06-15 10:30:00'));
		$options = $this->clientOptions($control);

		$this->assertSame('RTopt', $options['ID']);
		$this->assertIsInt($options['ServerTime']);
		$this->assertSame((new \DateTimeImmutable('2024-06-15 10:30:00'))->getTimestamp(), $options['OriginTime']);
		$this->assertTrue($options['UseServerTime']);
		$this->assertSame(1, $options['SignificantElements']);
		$this->assertSame('long', $options['Mode']);
		$this->assertSame('en-US', $options['Culture']);
		$this->assertNotSame('', $options['AbsoluteText']);
		$this->assertCount(7, $options['PartialCount']);
	}

	public function testClientOptionsCultureHyphenated()
	{
		[$control] = $this->makeOnPage();
		$control->setCulture('fr_FR');
		$this->assertSame('fr-FR', $this->clientOptions($control)['Culture']);
	}

	public function testClientOptionsUnitPatternsEnglish()
	{
		[$control] = $this->makeOnPage();
		$patterns = $this->clientOptions($control)['UnitPatterns'];

		$this->assertEqualsCanonicalizing(
			['year', 'month', 'week', 'day', 'hour', 'minute', 'second'],
			array_keys($patterns)
		);
		$this->assertSame('{0} minute', $patterns['minute']['one']);
		$this->assertSame('{0} minutes', $patterns['minute']['other']);
	}

	public function testClientOptionsUnitPatternsNarrowMode()
	{
		[$control] = $this->makeOnPage();
		$control->setMode(TRelativeTimeMode::Narrow);
		$options = $this->clientOptions($control);

		$this->assertSame('narrow', $options['Mode']);
		$this->assertSame('{0}m', $options['UnitPatterns']['minute']['one']);
	}

	public function testClientOptionsUnitPatternsRussianHasExtraPluralCategories()
	{
		[$control] = $this->makeOnPage();
		$control->setCulture('ru');
		$patterns = $this->clientOptions($control)['UnitPatterns'];

		$this->assertArrayHasKey('few', $patterns['minute']);
		$this->assertArrayHasKey('many', $patterns['minute']);
	}

	public function testClientOptionsShortModeUnitPatterns()
	{
		[$control] = $this->makeOnPage();
		$control->setMode(TRelativeTimeMode::Short);
		$options = $this->clientOptions($control);

		$this->assertSame('short', $options['Mode']);
		$this->assertSame('{0} min', $options['UnitPatterns']['minute']['one']);
	}

	public function testClientOptionsReflectFlags()
	{
		[$control] = $this->makeOnPage();
		$control->setUseServerTime(false);
		$control->setClickForDateTime(false);
		$control->setSignificantElements(2);
		$options = $this->clientOptions($control);

		$this->assertFalse($options['UseServerTime']);
		$this->assertFalse($options['ClickForDateTime']);
		$this->assertFalse($options['DurationOnly']);
		$control->setDurationOnly(true);
		$this->assertTrue($this->clientOptions($control)['DurationOnly']);
		$this->assertSame(2, $options['SignificantElements']);
	}

	public function testClientOptionsOriginDefaultsToNow()
	{
		[$control] = $this->makeOnPage();
		$options = $this->clientOptions($control);
		$this->assertEqualsWithDelta(time(), $options['OriginTime'], 5);
		$this->assertNotSame('', $options['AbsoluteText']);
	}

	// ================================================================================
	// Client script wiring
	// ================================================================================

	public function testGetClientClassName()
	{
		$this->assertSame('Prado.WebUI.TRelativeTime', (new TRelativeTimeScriptSpy())->exposeClientClassName());
	}

	public function testOnPreRenderRegistersScriptWhenEnhanced()
	{
		$page = new TPage();
		$control = new TRelativeTimeScriptSpy();
		$control->setCulture('en_US');
		$page->getControls()->add($control);

		$control->onPreRender(null);
		$this->assertTrue($control->scriptRegistered);
	}

	public function testOnPreRenderSkipsScriptWhenDisabled()
	{
		$page = new TPage();
		$control = new TRelativeTimeScriptSpy();
		$control->setCulture('en_US');
		$control->setEnabled(false);
		$page->getControls()->add($control);

		$control->onPreRender(null);
		$this->assertFalse($control->scriptRegistered);
	}

	public function testRegisterClientScriptRegistersEndScript()
	{
		$app = \Prado\Prado::getApplication();
		$originalService = $app->getService();
		$app->setService(new TPageService());

		$dir = sys_get_temp_dir() . '/prado-rt-assets-' . uniqid();
		mkdir($dir, 0o777, true);
		$alias = 'rtAssets' . str_replace('.', '', uniqid());
		\Prado\Prado::setPathOfAlias($alias, $dir);

		$assetManager = new TAssetManager();
		$assetManager->setBasePath($alias);
		$assetManager->setBaseUrl('/' . $alias);
		$assetManager->init(null);
		$app->setAssetManager($assetManager);

		try {
			[$control, $page] = $this->makeOnPage('RTscript');
			$this->invokeMethod($control, 'registerClientScript');
			$this->assertTrue($page->getClientScript()->isEndScriptRegistered('prado:RTscript'));
		} finally {
			$app->setService($originalService);
		}
	}

	// ================================================================================
	// JS package wiring (validates the 'relativetime' package in packages.php)
	// ================================================================================

	public function testClassMapResolvesTemplateShortNames()
	{
		// <com:TRelativeTime> in a template resolves through framework/classes.php.
		$this->assertSame(TRelativeTime::class, \Prado\Prado::usingClass('TRelativeTime'));
		$this->assertSame(TRelativeTimeMode::class, \Prado\Prado::usingClass('TRelativeTimeMode'));
	}

	public function testRelativeTimePackageIsRegistered()
	{
		[$folders, $packages, $dependencies] = require dirname(__DIR__, 5) . '/framework/Web/Javascripts/packages.php';

		$this->assertArrayHasKey('relativetime', $packages);
		$this->assertContains('prado/controls/relativetime.js', $packages['relativetime']);
		$this->assertArrayHasKey('relativetime', $dependencies);
		$this->assertSame(['jquery', 'prado', 'relativetime'], $dependencies['relativetime']);
	}

	public function testClientOptionsPartialCountOrder()
	{
		[$control] = $this->makeOnPage();
		$control->setYearsWithMonths(9);
		$control->setMinutesWithSeconds(7);
		$partial = $this->clientOptions($control)['PartialCount'];

		$this->assertSame(9, $partial[0]);
		$this->assertSame(7, $partial[5]);
		$this->assertSame(5, $partial[6]);
	}
}
