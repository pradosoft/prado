<?php


use Prado\I18N\core\CultureInfo;

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
		$this->assertEquals($culture->Countries['AE'], 'Émirats arabes unis');
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
		$this->assertEquals($culture->Languages['fr'], 'français');
	}

	public function testScripts()
	{
		$culture = new CultureInfo('fr');
		$this->assertEquals($culture->Scripts['Armn'], 'arménien');
	}

	public function testTimeZones()
	{
		$culture = new CultureInfo('it');

		$this->assertGreaterThanOrEqual(100, count($culture->TimeZones));
	}

	public function test_missing_english_names_returns_culture_code()
	{
		$culture = new CultureInfo('iw');
		$this->assertEquals($culture->getEnglishName(), 'iw');
	}
}
