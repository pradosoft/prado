<?php

use PHPUnit\Framework\TestCase;
use Prado\I18N\core\CultureInfo;
use Prado\I18N\core\CultureInfoUnits;

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
		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Invalid culture supplied');

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

		$this->expectException(\Exception::class);
		$culture->InvalidProperty;
	}

	public function testPropertySetThrowsException()
	{
		$culture = CultureInfo::getCultureInfo('en');

		$this->expectException(\Exception::class);
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
}
