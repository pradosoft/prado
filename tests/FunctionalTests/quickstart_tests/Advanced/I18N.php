<?php

//New Test
class I18NTestCase extends SeleniumTestCase
{
	function test ()
	{
		$this->open("../../demos/quickstart/index.php?page=Advanced.Samples.I18N.Home", "");
		$this->verifyTitle("Internationlization in PRADO", "");
		$this->verifyTextPresent("46.412,42 €", "");
		$this->verifyTextPresent("$12.40", "");
		$this->verifyTextPresent("€100.00", "");
		$this->verifyTextPresent("December 6, 2004", "");
		$this->clickAndWait("link=中文简体", "");
		$this->verifyTitle("PRADO 国际化", "");
		$this->verifyTextPresent("2004 十二月", "");
		$this->verifyTextPresent("US$ 12.40", "");
		$this->verifyTextPresent("46.412,42 €", "");
		$this->verifyTextPresent("€100.00 ", "");
		$this->clickAndWait("link=中文繁體", "");
		$this->verifyTitle("PRADO 國際化", "");
		$this->verifyTextPresent("2004年12月6日", "");
		$this->verifyTextPresent("US$12.40", "");
		$this->verifyTextPresent("46.412,42 €", "");
		$this->verifyTextPresent("€100.00", "");
		$this->clickAndWait("link=Deutsch", "");
		$this->verifyTitle("Internationalisierung in PRADO", "");
		$this->verifyTextPresent("6. Dezember 2004 ", "");
		$this->verifyTextPresent("$ 12,40", "");
		$this->verifyTextPresent("46.412,42 €", "");
		$this->verifyTextPresent("€100.00", "");
		$this->clickAndWait("link=Español", "");
		$this->verifyTitle("Internationlization en PRADO", "");
		$this->verifyTextPresent("6 de diciembre de 2004", "");
		$this->verifyTextPresent("US$12.40", "");
		$this->verifyTextPresent("46.412,42 €", "");
		$this->verifyTextPresent("€100.00", "");
		$this->clickAndWait("link=Français", "");
		$this->verifyTitle("Internationalisation dans PRADO", "");
		$this->verifyTextPresent("6 décembre 2004", "");
		$this->verifyTextPresent("12,40 $", "");
		$this->verifyTextPresent("46.412,42 €", "");
		$this->verifyTextPresent("€100.00", "");
		$this->clickAndWait("link=Polska", "");
		$this->verifyTitle("Internacjonalizacja w PRADO", "");
		$this->verifyTextPresent("6 grudnia 2004", "");
		$this->verifyTextPresent("US$ 12,40", "");
		$this->verifyTextPresent("46.412,42 €", "");
		$this->verifyTextPresent("€100.00", "");

	}
}

?>