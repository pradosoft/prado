<?php

Prado::using('System.I18N.core.*');
class CultureInfoTest extends UnitTestCase
{
	function test_missing_english_names_returns_culture_code()
	{
		$culture = new CultureInfo('iw');
		$this->assertEqual($culture->getEnglishName(), 'iw');
	}
}

?>