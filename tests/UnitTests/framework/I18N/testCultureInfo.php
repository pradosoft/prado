<?php

Prado::using('System.I18N.core.CultureInfo');

class testCultureInfo extends UnitTestCase
{
	protected $culture;

	function testCultureInfo()
	{
		$this->UnitTestCase();
	}

	function setUp()
	{
		$this->culture = CultureInfo::getInvariantCulture();
	}

	function testCultureName()
	{
		$name = 'en';

		$this->assertEqual($name, $this->culture->Name);

		//the default/invariant culture should be neutral
		$this->assertTrue($this->culture->IsNeutralCulture);
	}

	function testCultureList()
	{
		$allCultures = CultureInfo::getCultures();
		$neutralCultures = CultureInfo::getCultures(CultureInfo::NEUTRAL);
		$specificCultures = CultureInfo::getCultures(CultureInfo::SPECIFIC);

		//there should be 246 cultures all together.
		$this->assertEqual(count($allCultures),246);
		$this->assertEqual(count($neutralCultures),76);
		$this->assertEqual(count($specificCultures),170);

	}

	function testParentCultures()
	{
		$zh_CN = new CultureInfo('zh_CN');
		$parent = $zh_CN->Parent;
		$grandparent = $parent->Parent;

		$this->assertEqual($zh_CN->Name, 'zh_CN');
		$this->assertEqual($parent->Name, 'zh');
		$this->assertEqual($grandparent->Name, 'en');
		$this->assertEqual($grandparent->Parent->Name, 'en');
	}

	function testCountryNames()
	{
		$culture = new CultureInfo('fr_FR');
		$this->assertEqual($culture->Countries['AE'], 'Émirats arabes unis');
	}

	function testCurrencies()
	{
		$culture = new CultureInfo('en_AU');
		$au = array('$', 'Australian Dollar');
		$this->assertEqual($au, $culture->Currencies['AUD']);
	}

	function testLanguages()
	{
		$culture = new CultureInfo('fr_BE');
		$this->assertEqual($culture->Languages['fr'], 'français');
	}

	function testScripts()
	{
		$culture = new CultureInfo('fr');
		$this->assertEqual($culture->Scripts['Armn'], 'arménien');
	}

	function testTimeZones()
	{
		$culture = new CultureInfo('fi');
		$zone = array(
			"America/Los_Angeles",
            "Tyynenmeren normaaliaika",
            "PST",
            "Tyynenmeren kesäaika",
            "PDT",
            "Los Angeles");
        $this->assertEqual($culture->TimeZones[1],$zone);
	}

}

?>