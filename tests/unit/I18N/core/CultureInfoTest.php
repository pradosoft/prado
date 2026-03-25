<?php


use Prado\I18N\core\CultureInfo;
use Prado\I18N\core\CultureInfoUnits;

class CultureInfoTest extends PHPUnit\Framework\TestCase
{
	protected $culture;

	protected function setUp(): void
	{
		$this->culture = CultureInfo::getInvariantCulture();
	}

	public function testCultureName()
	{
		$name = 'en';

		$this->assertEquals($name, $this->culture->Name);

		//the default/invariant culture should be neutral
		$this->assertTrue($this->culture->IsNeutralCulture);
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
		$this->assertEquals('Émirats arabes unis', $culture->Countries['AE'], );
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
		$this->assertEquals('arménien', $culture->Scripts['Armn'], );
	}

	public function testTimeZones()
	{
		$culture = new CultureInfo('it');

		$this->assertGreaterThanOrEqual(88, count($culture->TimeZones));
	}

	public function test_missing_english_names_returns_culture_code()
	{
		$culture = new CultureInfo('iw');
		$this->assertEquals('iw', $culture->getEnglishName());
	}
	public function testUnits()
	{
		$culture = new CultureInfo('en');
		
		$this->assertGreaterThanOrEqual(23, count($culture->Units));
	}
	
	public function test_get_unit()
	{
		$culture = new CultureInfo('en_US');
		
		$unitName = $culture->getUnit(Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_GIGABYTE);
			
		$this->assertEquals('gigabytes', $unitName);
	}
	
	public function test_get_unit_not_found()
	{
		$culture = new CultureInfo('en_US');
		
		$unitName = $culture->getUnit(Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_QUETTABYTE);
			
		$this->assertNull($unitName);
	}
	
	public function test_format_number()
	{
		$culture = new CultureInfo('en_US');
		
		$formattedNumber = $culture->formatNumber(1234.25);
		$this->assertEquals('1,234.25', $formattedNumber);
	}

	public function test_format_unit()
	{
		$culture = new CultureInfo('en_US');
		
		$unitFormatted = $culture->formatUnit(1, Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_GIGABYTE);
		$this->assertEquals('1 gigabyte', $unitFormatted);
		
		$unitFormatted = $culture->formatUnit(10, Prado\I18N\core\CultureInfoUnits::TYPE_DIGITAL_GIGABYTE);
		$this->assertEquals('10 gigabytes', $unitFormatted);
	}

	public function test_format_per_unit()
	{
		$culture = new CultureInfo('en_US');
		
		$unitFormatted = $culture->formatPerUnit(1, Prado\I18N\core\CultureInfoUnits::TYPE_LENGTH_METER);
		$this->assertEquals('1 per meter', $unitFormatted);
	}

}
