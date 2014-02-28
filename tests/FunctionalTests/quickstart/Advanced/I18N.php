<?php

//New Test
class QuickstartI18NTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?notheme=true&page=Advanced.Samples.I18N.Home&amp;lang=en&amp;notheme=true");
		$this->assertTextPresent("Internationlization in PRADO", "");
		$this->assertTextPresent("46.412,42 €", "");
		$this->assertTextPresent("$12.40", "");
		$this->assertTextPresent("€100.00", "");
		$this->assertTextPresent("December 6, 2004", "");
		$this->url("../../demos/quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=zh&amp;notheme=true");
		$this->assertTextPresent("PRADO 国际化", "");
		$this->assertTextPresent("2004 十二月", "");
		$this->assertTextPresent("US$ 12.40", "");
		$this->assertTextPresent("46.412,42 €", "");
		$this->assertTextPresent("€100.00 ", "");
		$this->url("../../demos/quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=zh_TW&amp;notheme=true");
		$this->assertTextPresent("PRADO 國際化", "");
		$this->assertTextPresent("2004年12月6日", "");
		$this->assertTextPresent("US$12.40", "");
		$this->assertTextPresent("46.412,42 €", "");
		$this->assertTextPresent("€100.00", "");
		$this->url("../../demos/quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=de&amp;notheme=true");
		$this->assertTextPresent("Internationalisierung in PRADO", "");
		$this->assertTextPresent("6. Dezember 2004 ", "");
		$this->assertTextPresent("$ 12,40", "");
		$this->assertTextPresent("46.412,42 €", "");
		$this->assertTextPresent("€100.00", "");
		$this->url("../../demos/quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=es&amp;notheme=true");
		$this->assertTextPresent("Internationlization en PRADO", "");
		$this->assertTextPresent("6 de diciembre de 2004", "");
		$this->assertTextPresent("US$12.40", "");
		$this->assertTextPresent("46.412,42 €", "");
		$this->assertTextPresent("€100.00", "");
		$this->url("../../demos/quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=fr&amp;notheme=true");
		$this->assertTextPresent("Internationalisation avec PRADO", "");
		$this->assertTextPresent("6 décembre 2004", "");
		$this->assertTextPresent("12,40 $", "");
		$this->assertTextPresent("46.412,42 €", "");
		$this->assertTextPresent("€100.00", "");
		$this->url("../../demos/quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=pl&amp;notheme=true");
		$this->assertTextPresent("Internacjonalizacja w PRADO", "");
		$this->assertTextPresent("6 grudnia 2004", "");
		$this->assertTextPresent("US$ 12,40", "");
		$this->assertTextPresent("46.412,42 €", "");
		$this->assertTextPresent("€100.00", "");

	}
}
