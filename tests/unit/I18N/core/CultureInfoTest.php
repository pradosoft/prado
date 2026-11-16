<?php

namespace Prado\Test\Unit\I18N\core;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Prado\I18N\core\CultureInfo;
use Prado\I18N\core\CultureInfoUnits;
use Prado\I18N\TGlobalization;
use Prado\Prado;
use Prado\Test\Unit\PradoUnit;

/**
 * A culture whose resource data is set by the test, keyed by bundle.
 */
class CultureInfoDataFixture extends CultureInfo
{
	public array $info = [];

	public function findInfo($path = '/', $key = null)
	{
		return $this->info[$key] ?? [];
	}
}

class CultureInfoTest extends TestCase
{
	public function testGetCultureInfoReturnsSameInstance()
	{
		$culture1 = CultureInfo::getCultureInfo('en_US');
		$culture2 = CultureInfo::getCultureInfo('en_US');

		$this->assertSame($culture1, $culture2);
	}

	public function testGetCultureInfoDifferentCulturesReturnDifferentInstances()
	{
		$cultureUS = CultureInfo::getCultureInfo('en_US');
		$cultureFR = CultureInfo::getCultureInfo('fr_FR');

		$this->assertNotSame($cultureUS, $cultureFR);
	}

	public function testGetCultureInfoWithNullReturnsInvariantCulture()
	{
		$culture = CultureInfo::getCultureInfo(null);

		$this->assertEquals('en', $culture->getName());
	}

	public function testGetCultureInfoCachesMultipleCultures()
	{
		$cultureUS = CultureInfo::getCultureInfo('en_US');
		$cultureFR = CultureInfo::getCultureInfo('fr_FR');
		$cultureDE = CultureInfo::getCultureInfo('de_DE');

		$cultureUS2 = CultureInfo::getCultureInfo('en_US');
		$cultureFR2 = CultureInfo::getCultureInfo('fr_FR');
		$cultureDE2 = CultureInfo::getCultureInfo('de_DE');

		$this->assertSame($cultureUS, $cultureUS2);
		$this->assertSame($cultureFR, $cultureFR2);
		$this->assertSame($cultureDE, $cultureDE2);
	}

	public function testGetCultureInfoValidatesCultureName()
	{
		$culture = CultureInfo::getCultureInfo('en_US');

		$this->assertEquals('en_US', $culture->getName());
	}

	public function testValidCulture()
	{
		$this->assertTrue(CultureInfo::validCulture('en_US'));
		$this->assertTrue(CultureInfo::validCulture('fr_FR'));
		$this->assertFalse(CultureInfo::validCulture('invalid_culture'));
	}

	/**
	 * ResourceBundle::getLocales() stores some locales in BCP 47 hyphen form
	 * (e.g. "en-US") and others in POSIX underscore form (e.g. "de_DE"),
	 * depending on ICU version and locale.  validCulture() must bridge all
	 * directions so that both POSIX and BCP 47 inputs work regardless of which
	 * format the underlying list uses for a given locale.
	 */
	public function testValidCultureBridgesPosixAndBcp47Forms()
	{
		// POSIX input — works whether the ICU list has "de_DE" or "de-DE"
		$this->assertTrue(CultureInfo::validCulture('de_DE'));
		// BCP 47 input — works whether the ICU list has "de-DE" or "de_DE"
		$this->assertTrue(CultureInfo::validCulture('de-DE'));
	}

	public function testGetNativeName()
	{
		$culture = CultureInfo::getCultureInfo('en_US');

		$this->assertNotEmpty($culture->getNativeName());
	}

	public function testGetEnglishName()
	{
		$culture = CultureInfo::getCultureInfo('en_US');

		$this->assertNotEmpty($culture->getEnglishName());
	}

	public function testGetInvariantCulture()
	{
		$invariant1 = CultureInfo::getInvariantCulture();
		$invariant2 = CultureInfo::getInvariantCulture();

		$this->assertSame($invariant1, $invariant2);
		$this->assertEquals('en', $invariant1->getName());
	}

	public function testGetIsNeutralCulture()
	{
		$neutralCulture = CultureInfo::getCultureInfo('en');
		$specificCulture = CultureInfo::getCultureInfo('en_US');

		$this->assertTrue($neutralCulture->getIsNeutralCulture());
		$this->assertFalse($specificCulture->getIsNeutralCulture());
	}

	/**
	 * TGlobalization::setCulture() converts BCP 47 hyphens to POSIX underscores,
	 * so a BCP 47 script-subtag locale like "zh-Hant-TW" arrives as "zh_Hant_TW"
	 * (length 10).  getIsNeutralCulture() uses a strlen() == 2 test that relies
	 * on the POSIX convention; it must correctly classify such a culture as
	 * specific (not neutral).
	 */
	public function testGetIsNeutralCulturePosixNormalizedScriptSubtagIsSpecific()
	{
		// "zh-Hant-TW" → POSIX-normalised to "zh_Hant_TW" (length 10, has region subtag)
		$specific = new CultureInfo('zh_Hant_TW');
		$this->assertFalse($specific->getIsNeutralCulture());

		// "zh-Hant" → POSIX-normalised to "zh_Hant" (length 7, script-only, no region)
		$scriptOnly = new CultureInfo('zh_Hant');
		$this->assertFalse($scriptOnly->getIsNeutralCulture());
	}

	public function testToString()
	{
		$culture = CultureInfo::getCultureInfo('en_US');

		$this->assertEquals('en_US', (string) $culture);
	}

	public function testGetCountries()
	{
		$culture = CultureInfo::getCultureInfo('fr_FR');

		$countries = $culture->getCountries();
		$this->assertNotEmpty($countries);
		$this->assertArrayHasKey('AE', $countries);
	}

	public function testGetCurrencies()
	{
		$culture = CultureInfo::getCultureInfo('en_AU');

		$currencies = $culture->getCurrencies();
		$this->assertNotEmpty($currencies);
		$this->assertArrayHasKey('AUD', $currencies);
		$this->assertEquals('$', $currencies['AUD'][0]);
	}

	public function testGetLanguages()
	{
		$culture = CultureInfo::getCultureInfo('fr');

		$languages = $culture->getLanguages();
		$this->assertNotEmpty($languages);
		$this->assertArrayHasKey('fr', $languages);
	}

	public function testGetScripts()
	{
		$culture = CultureInfo::getCultureInfo('fr');

		$scripts = $culture->getScripts();
		$this->assertNotEmpty($scripts);
	}

	public function testGetTimeZones()
	{
		$culture = CultureInfo::getCultureInfo('it');

		$timeZones = $culture->getTimeZones();
		$this->assertGreaterThanOrEqual(88, count($timeZones));
	}

	public function testSetInvalidCultureThrowsException()
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$this->expectExceptionMessage('Invalid culture');

		new CultureInfo('invalid@culture');
	}

	public function testSetCultureWithEmptyString()
	{
		$culture = new CultureInfo('');

		$this->assertEquals('en', $culture->getName());
	}

	public function testPropertyGetAccess()
	{
		$culture = CultureInfo::getCultureInfo('en');

		$this->assertEquals('en', $culture->Name);
	}

	public function testPropertyGetAccessThrowsExceptionForInvalidProperty()
	{
		$culture = CultureInfo::getCultureInfo('en');

		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$this->expectExceptionMessage("CultureInfo has no property 'InvalidProperty'.");
		$culture->InvalidProperty;
	}

	public function testPropertySetThrowsException()
	{
		$culture = CultureInfo::getCultureInfo('en');

		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		$this->expectExceptionMessage("CultureInfo property 'Name' is read-only.");
		$culture->Name = 'fr';
	}

	public function testFormatNumberPercent()
	{
		$culture = CultureInfo::getCultureInfo('en_US');

		$formattedNumber = $culture->formatNumber(1234.256, \NumberFormatter::PERCENT);
		$this->assertEquals('123,426%', $formattedNumber);
	}

	public function testFormatNumberBadFormat()
	{
		$culture = CultureInfo::getCultureInfo('en_US');
		$this->expectException(\IntlException::class);
		$culture->formatNumber(1234.25, -5000);
	}

	public function testFormatUnit()
	{
		$culture = CultureInfo::getCultureInfo('en_US');

		$unitFormatted = $culture->formatUnit(1, CultureInfoUnits::TYPE_DIGITAL_GIGABYTE);
		$this->assertEquals('1 gigabyte', $unitFormatted);

		$unitFormatted = $culture->formatUnit(10, CultureInfoUnits::TYPE_DIGITAL_GIGABYTE);
		$this->assertEquals('10 gigabytes', $unitFormatted);
	}

	public function testFormatUnitNotFound()
	{
		$culture = CultureInfo::getCultureInfo('en_US');

		$unitFormatted = $culture->formatUnit(1, CultureInfoUnits::TYPE_DIGITAL_QUETTABYTE);
		$this->assertNull($unitFormatted);
	}

	public function testFormatPerUnit()
	{
		$culture = CultureInfo::getCultureInfo('en_US');

		$unitFormatted = $culture->formatPerUnit(1, CultureInfoUnits::TYPE_LENGTH_METER);
		$this->assertEquals('1 per meter', $unitFormatted);
	}

	public function testFormatPerUnitNoPer()
	{
		$culture = CultureInfo::getCultureInfo('en_US');

		$noPerUnit = $culture->formatPerUnit(1, CultureInfoUnits::TYPE_DIGITAL_BYTE);
		$this->assertNull($noPerUnit);
	}

	public function testGetUnitPatternsLongDefault()
	{
		$culture = CultureInfo::getCultureInfo('en_US');

		$patterns = $culture->getUnitPatterns(CultureInfoUnits::TYPE_DURATION_MINUTE);
		$this->assertEquals('{0} minute', $patterns['one']);
		$this->assertEquals('{0} minutes', $patterns['other']);
	}

	public function testGetUnitPatternsWidths()
	{
		$culture = CultureInfo::getCultureInfo('en_US');

		$this->assertEquals('{0} min', $culture->getUnitPatterns(CultureInfoUnits::TYPE_DURATION_MINUTE, 'short')['one']);
		$this->assertEquals('{0}m', $culture->getUnitPatterns(CultureInfoUnits::TYPE_DURATION_MINUTE, 'narrow')['one']);
	}

	public function testGetUnitPatternsOnlyPluralKeys()
	{
		$culture = CultureInfo::getCultureInfo('en_US');

		$patterns = $culture->getUnitPatterns(CultureInfoUnits::TYPE_DURATION_MINUTE);
		$this->assertEqualsCanonicalizing(['one', 'other'], array_keys($patterns));
		$this->assertArrayNotHasKey('dnam', $patterns);
		$this->assertArrayNotHasKey('per', $patterns);
	}

	public function testGetUnitPatternsPluralCategoriesForRussian()
	{
		$culture = CultureInfo::getCultureInfo('ru');

		$patterns = $culture->getUnitPatterns(CultureInfoUnits::TYPE_DURATION_MINUTE);
		$this->assertArrayHasKey('few', $patterns);
		$this->assertArrayHasKey('many', $patterns);
		$this->assertArrayHasKey('one', $patterns);
		$this->assertArrayHasKey('other', $patterns);
	}

	public function testGetUnitPatternsNarrowFallsBackToShort()
	{
		$culture = CultureInfo::getCultureInfo('ru');

		// Russian has no narrow duration units; the width falls back to short.
		$narrow = $culture->getUnitPatterns(CultureInfoUnits::TYPE_DURATION_MINUTE, 'narrow');
		$short = $culture->getUnitPatterns(CultureInfoUnits::TYPE_DURATION_MINUTE, 'short');
		$this->assertNotEmpty($narrow);
		$this->assertEquals($short, $narrow);
	}

	public function testGetUnitPatternsUnknownWidthDefaultsToLong()
	{
		$culture = CultureInfo::getCultureInfo('en_US');

		$this->assertEquals(
			$culture->getUnitPatterns(CultureInfoUnits::TYPE_DURATION_HOUR, 'long'),
			$culture->getUnitPatterns(CultureInfoUnits::TYPE_DURATION_HOUR, 'bogus')
		);
	}

	public function testGetUnitPatternsUnknownUnitReturnsEmpty()
	{
		$culture = CultureInfo::getCultureInfo('en_US');

		$this->assertSame([], $culture->getUnitPatterns('duration-nonexistent'));
	}

	public function testGetRelativeTimePatternsLongDefault()
	{
		$patterns = CultureInfo::getCultureInfo('en_US')->getRelativeTimePatterns(CultureInfoUnits::TYPE_DURATION_MINUTE);

		$this->assertEquals('{0} minute ago', $patterns['past']['one']);
		$this->assertEquals('{0} minutes ago', $patterns['past']['other']);
		$this->assertEquals('in {0} minute', $patterns['future']['one']);
		$this->assertEquals('in {0} minutes', $patterns['future']['other']);
	}

	public function testGetRelativeTimePatternsWidths()
	{
		$culture = CultureInfo::getCultureInfo('en_US');

		$short = $culture->getRelativeTimePatterns(CultureInfoUnits::TYPE_DURATION_MINUTE, 'short')['past']['other'];
		$this->assertEquals('{0} min. ago', $short);

		// The narrow minute relative-time pattern ({0}m ago) exists only in newer CLDR.
		// Older ICU omits the narrow field, so the width falls back to short (narrow to short to long).
		$narrow = $culture->getRelativeTimePatterns(CultureInfoUnits::TYPE_DURATION_MINUTE, 'narrow')['past']['other'];
		$this->assertContains($narrow, ['{0}m ago', $short]);
	}

	public function testGetRelativeTimePatternsRussianCategoriesAndInflection()
	{
		$patterns = CultureInfo::getCultureInfo('ru')->getRelativeTimePatterns(CultureInfoUnits::TYPE_DURATION_MINUTE);

		$this->assertArrayHasKey('few', $patterns['past']);
		$this->assertArrayHasKey('many', $patterns['past']);
		// The relative pattern inflects the unit (accusative), unlike the bare unit pattern.
		$this->assertEquals('{0} минуту назад', $patterns['past']['one']);
	}

	public function testGetRelativeTimePatternsUnknownWidthDefaultsToLong()
	{
		$culture = CultureInfo::getCultureInfo('en_US');

		$this->assertEquals(
			$culture->getRelativeTimePatterns(CultureInfoUnits::TYPE_DURATION_HOUR, 'long'),
			$culture->getRelativeTimePatterns(CultureInfoUnits::TYPE_DURATION_HOUR, 'bogus')
		);
	}

	public function testGetRelativeTimePatternsUnknownUnitReturnsEmpty()
	{
		$this->assertSame([], CultureInfo::getCultureInfo('en_US')->getRelativeTimePatterns('duration-nonexistent'));
	}

	public function testSelectPluralCategoryEnglish()
	{
		$culture = CultureInfo::getCultureInfo('en_US');

		$this->assertSame('one', $culture->selectPluralCategory(1));
		$this->assertSame('other', $culture->selectPluralCategory(0));
		$this->assertSame('other', $culture->selectPluralCategory(5));
	}

	public function testSelectPluralCategoryRussian()
	{
		$culture = CultureInfo::getCultureInfo('ru');

		$this->assertSame('one', $culture->selectPluralCategory(1));
		$this->assertSame('few', $culture->selectPluralCategory(2));
		$this->assertSame('many', $culture->selectPluralCategory(5));
		$this->assertSame('one', $culture->selectPluralCategory(21));
	}

	public function testSelectPluralCategoryArabicSixCategories()
	{
		$culture = CultureInfo::getCultureInfo('ar');

		$this->assertSame('zero', $culture->selectPluralCategory(0));
		$this->assertSame('two', $culture->selectPluralCategory(2));
		$this->assertSame('few', $culture->selectPluralCategory(3));
		$this->assertSame('many', $culture->selectPluralCategory(11));
		$this->assertSame('other', $culture->selectPluralCategory(100));
	}

	public function testMissingEnglishNameReturnsCultureCode()
	{
		$culture = new CultureInfo('iw');

		$this->assertEquals('iw', $culture->getEnglishName());
	}

	public function testCultureList()
	{
		$allCultures = CultureInfo::getCultures();
		$neutralCultures = CultureInfo::getCultures(CultureInfo::NEUTRAL);
		$specificCultures = CultureInfo::getCultures(CultureInfo::SPECIFIC);

		$this->assertGreaterThanOrEqual(600, count($allCultures));
		$this->assertGreaterThanOrEqual(100, count($neutralCultures));
		$this->assertGreaterThanOrEqual(500, count($specificCultures));
	}

	public function testCountryNames()
	{
		$culture = new CultureInfo('fr_FR');
		$this->assertEquals('Émirats arabes unis', $culture->Countries['AE']);
	}

	public function testCurrencies()
	{
		$culture = new CultureInfo('en_AU');
		$au = ['$', 'Australian Dollar'];
		$this->assertEquals($au, $culture->Currencies['AUD']);
	}

	public function testLanguages()
	{
		$culture = new CultureInfo('fr');
		$this->assertEquals('français', $culture->Languages['fr']);
	}

	public function testScripts()
	{
		$culture = new CultureInfo('fr');
		$this->assertEquals('arménien', $culture->Scripts['Armn']);
	}

	public function testUnits()
	{
		$culture = new CultureInfo('en');

		$this->assertGreaterThanOrEqual(23, count($culture->Units));
	}

	public function testGetUnit()
	{
		$culture = new CultureInfo('en_US');

		$unitName = $culture->getUnit(CultureInfoUnits::TYPE_DIGITAL_GIGABYTE);

		$this->assertEquals('gigabytes', $unitName);
	}

	public function testGetUnitNotFound()
	{
		$culture = new CultureInfo('en_US');

		$unitName = $culture->getUnit(CultureInfoUnits::TYPE_DIGITAL_QUETTABYTE);

		$this->assertNull($unitName);
	}

	public function testFormatNumber()
	{
		$culture = new CultureInfo('en_US');

		$formattedNumber = $culture->formatNumber(1234.25);
		$this->assertEquals('1,234.25', $formattedNumber);

		$formattedNumber = $culture->formatNumber(1234.25, \NumberFormatter::DECIMAL);
		$this->assertEquals('1,234.25', $formattedNumber);
	}

	// ------- getCultureInfo of the application culture -------

	/**
	 * Runs $fn with an empty culture cache and the given application globalization.
	 * @param ?TGlobalization $globalization the globalization of the application.
	 */
	private function withGlobalization(?TGlobalization $globalization, callable $fn): void
	{
		$app = Prado::getApplication();
		$saved = PradoUnit::getProp($app, '_globalization');
		$infos = PradoUnit::snapshotStatic(CultureInfo::class, ['cultureInfos']);
		PradoUnit::setProp($app, '_globalization', $globalization);
		PradoUnit::setProp(CultureInfo::class, 'cultureInfos', []);
		try {
			$fn();
		} finally {
			PradoUnit::setProp($app, '_globalization', $saved);
			PradoUnit::restoreStatic(CultureInfo::class, $infos);
		}
	}

	public function testGetCultureInfoOfNullUsesTheGlobalizationCulture()
	{
		$globalization = new TGlobalization();
		$globalization->setCulture('fr_FR');
		$this->withGlobalization($globalization, function () {
			$info = CultureInfo::getCultureInfo();

			$this->assertSame('fr_FR', $info->getName());
			$this->assertSame($info, CultureInfo::getCultureInfo());
			$this->assertSame($info, CultureInfo::getCultureInfo('fr_FR'));
		});
	}

	public function testGetCultureInfoOfNullWithoutAGlobalizationCultureIsTheDefault()
	{
		$globalization = new TGlobalization();
		PradoUnit::setProp($globalization, '_culture', null);
		$this->withGlobalization($globalization, function () {
			$info = CultureInfo::getCultureInfo();

			$this->assertSame('en', $info->getName());
			$this->assertSame($info, CultureInfo::getCultureInfo(''));
		});
	}

	public function testGetCultureInfoOfNullWithoutAGlobalizationIsTheDefault()
	{
		$this->withGlobalization(null, function () {
			$info = CultureInfo::getCultureInfo();

			$this->assertSame('en', $info->getName());
			$this->assertSame($info, CultureInfo::getCultureInfo());
		});
	}

	public function testGetCultureInfoOfNullWithoutAnApplicationIsTheDefault()
	{
		$this->withGlobalization(null, function () {
			$app = Prado::getApplication();
			Prado::setApplication(null);
			try {
				$info = CultureInfo::getCultureInfo();
				$this->assertSame('en', $info->getName());
				$this->assertSame($info, CultureInfo::getCultureInfo());
			} finally {
				Prado::setApplication($app);
			}
		});
	}

	// ------- properties, names, and resources -------

	public function testPropertySetCallsTheProtectedSetter()
	{
		$culture = new CultureInfo('en');

		$culture->Culture = 'fr_FR';
		$this->assertSame('fr_FR', $culture->getName());

		$culture->Culture = '';
		$this->assertSame('', $culture->getName());
	}

	public function testGetCulturesOfAnUnknownTypeIsAll()
	{
		$this->assertSame(CultureInfo::getCultures(), CultureInfo::getCultures(99));
	}

	public function testValidCultureOfAnUnknownHyphenatedCulture()
	{
		$this->assertFalse(CultureInfo::validCulture('xx-YY'));
	}

	public function testNativeNameOfANeutralCulture()
	{
		$this->assertSame('English', (new CultureInfo('en'))->getNativeName());
	}

	public function testNamesOfAnUnknownRegion()
	{
		$culture = new CultureInfo('en_QQ');

		$this->assertSame('English', $culture->getNativeName());
		$this->assertSame('English', $culture->getEnglishName());
	}

	public function testNamesWithARegion()
	{
		$this->assertSame('français (France)', (new CultureInfo('fr_FR'))->getNativeName());
		$this->assertSame('French (France)', (new CultureInfo('fr_FR'))->getEnglishName());
	}

	public function testGetCalendarReadsTheCoreBundle()
	{
		$culture = new CultureInfo('en_US');

		$this->assertSame($culture->findInfo('calendar/default', 'Core'), $culture->getCalendar());
	}

	public function testFindInfoOfAnUnknownBundleIsEmpty()
	{
		$culture = new CultureInfo('en_US');

		$this->assertSame([], $culture->findInfo('Languages', 'Unknown'));
	}

	public function testLoadCultureDataOfAnUnknownBundleLoadsNothing()
	{
		$snapshot = PradoUnit::snapshotStatic(CultureInfo::class, ['bundleNames', 'data']);
		try {
			$culture = new CultureInfo('de_DE');
			PradoUnit::setProp(CultureInfo::class, 'data', []);

			PradoUnit::setProp(CultureInfo::class, 'bundleNames', []);
			PradoUnit::invoke($culture, 'loadCultureData', 'Core');
			$this->assertSame([], PradoUnit::getProp(CultureInfo::class, 'data'));

			PradoUnit::setProp(CultureInfo::class, 'bundleNames', ['Other' => null]);
			PradoUnit::invoke($culture, 'loadCultureData', 'Core');
			$this->assertSame([], PradoUnit::getProp(CultureInfo::class, 'data'));
		} finally {
			PradoUnit::restoreStatic(CultureInfo::class, $snapshot);
		}
	}

	public function testSearchResourcesOfAnArrayKeepsTheArray()
	{
		$culture = new CultureInfo('en');

		$this->assertSame(['a' => 'x'], PradoUnit::invoke($culture, 'searchResources', ['a' => ['x']], 'a'));
	}

	public function testSimplify()
	{
		$culture = new CultureInfo('en');

		$this->assertSame('x', PradoUnit::invoke($culture, 'simplify', 'x'));
		$this->assertNull(PradoUnit::invoke($culture, 'simplify', null));
		$this->assertSame(
			['k' => 'v', 'm' => ['a', 'b'], 's' => 's'],
			PradoUnit::invoke($culture, 'simplify', ['k' => ['v'], 'm' => ['a', 'b'], 's' => 's'])
		);
		$this->assertSame(['k' => 'v'], PradoUnit::invoke($culture, 'simplify', ['k' => ['v']]));
	}

	public function testGetTimeZonesIsComputedOnce()
	{
		$culture = new CultureInfo('it');

		$this->assertSame($culture->getTimeZones(), (new CultureInfo('fr'))->getTimeZones());
	}

	/**
	 * getTimeZones() and getCurrencies() keep their first result in a static variable,
	 * so the empty and one-entry resources run in a fresh process.
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testTimeZonesAndCurrenciesOfSmallResources()
	{
		$culture = new CultureInfoDataFixture('en');

		$this->assertNull($culture->getTimeZones());
		$culture->info['zoneStrings'] = ['Africa:Abidjan' => []];
		$this->assertSame(['Africa/Abidjan'], $culture->getTimeZones());
		$culture->info['zoneStrings'] = ['Europe:Paris' => []];
		$this->assertSame(['Africa/Abidjan'], $culture->getTimeZones());

		$this->assertSame([], $culture->getCurrencies());
		$culture->info['Currencies'] = ['USD' => ['$', 'US Dollar']];
		$this->assertSame([], $culture->getCurrencies());
	}

	// ------- units and plurals -------

	public function testFormatUnitOfEachPluralAndAMissingUnitOrPattern()
	{
		$culture = new CultureInfo('en_US');

		$this->assertSame('1 kilometer', $culture->formatUnit(1, CultureInfoUnits::TYPE_LENGTH_KILOMETER));
		$this->assertSame('2 kilometers', $culture->formatUnit(2, CultureInfoUnits::TYPE_LENGTH_KILOMETER));
		$this->assertNull($culture->formatUnit(1, 'length-unknown'));
		$this->assertNull($culture->formatUnit(2, 'length-unknown'));
		$this->assertNull($culture->formatUnit(1, 'length'));
		$this->assertNull($culture->formatUnit(2, 'length'));
	}

	public function testFormatPerUnitOfAMissingUnitOrPattern()
	{
		$culture = new CultureInfo('en_US');

		$this->assertSame('3 per kilometer', $culture->formatPerUnit(3, CultureInfoUnits::TYPE_LENGTH_KILOMETER));
		$this->assertNull($culture->formatPerUnit(3, 'length-unknown'));
		$this->assertNull($culture->formatPerUnit(3, 'length'));
	}

	public function testGetUnitPatternsOfACategoryIsEmpty()
	{
		$this->assertSame([], (new CultureInfo('en_US'))->getUnitPatterns('length'));
	}

	public function testSelectPluralCategoryOfAnUnformattableNumberIsOther()
	{
		$this->assertSame('other', (new CultureInfo('en'))->selectPluralCategory(NAN));
	}

	public function testEnglishNameOfANeutralCulture()
	{
		$this->assertSame('English', (new CultureInfo('en'))->getEnglishName());
		$this->assertSame('French', (new CultureInfo('fr'))->getEnglishName());
	}
}
