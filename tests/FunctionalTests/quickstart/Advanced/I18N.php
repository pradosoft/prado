<?php

//New Test
class QuickstartI18NTestCase extends PradoGenericSelenium2Test
{
	function test ()
	{
		$this->url("../../demos/quickstart/index.php?notheme=true&page=Advanced.Samples.I18N.Home&amp;lang=en&amp;notheme=true");
		$this->assertContains("Internationlization in PRADO", $this->source());
		$this->assertContains("46.412,42 €", $this->source());
		$this->assertContains("$12.40", $this->source());
		$this->assertContains("€100.00", $this->source());
		$this->assertContains("December 6, 2004", $this->source());
		$this->url("../../demos/quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=zh&amp;notheme=true");
		$this->assertContains("PRADO 国际化", $this->source());
		$this->assertContains("2004 十二月", $this->source());
		$this->assertContains("US$ 12.40", $this->source());
		$this->assertContains("46.412,42 €", $this->source());
		$this->assertContains("€100.00 ", $this->source());
		$this->url("../../demos/quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=zh_TW&amp;notheme=true");
		$this->assertContains("PRADO 國際化", $this->source());
		$this->assertContains("2004年12月6日", $this->source());
		$this->assertContains("US$12.40", $this->source());
		$this->assertContains("46.412,42 €", $this->source());
		$this->assertContains("€100.00", $this->source());
		$this->url("../../demos/quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=de&amp;notheme=true");
		$this->assertContains("Internationalisierung in PRADO", $this->source());
		$this->assertContains("6. Dezember 2004 ", $this->source());
		$this->assertContains("$ 12,40", $this->source());
		$this->assertContains("46.412,42 €", $this->source());
		$this->assertContains("€100.00", $this->source());
		$this->url("../../demos/quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=es&amp;notheme=true");
		$this->assertContains("Internationlization en PRADO", $this->source());
		$this->assertContains("6 de diciembre de 2004", $this->source());
		$this->assertContains("US$12.40", $this->source());
		$this->assertContains("46.412,42 €", $this->source());
		$this->assertContains("€100.00", $this->source());
		$this->url("../../demos/quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=fr&amp;notheme=true");
		$this->assertContains("Internationalisation avec PRADO", $this->source());
		$this->assertContains("6 décembre 2004", $this->source());
		$this->assertContains("12,40 $", $this->source());
		$this->assertContains("46.412,42 €", $this->source());
		$this->assertContains("€100.00", $this->source());
		$this->url("../../demos/quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=pl&amp;notheme=true");
		$this->assertContains("Internacjonalizacja w PRADO", $this->source());
		$this->assertContains("6 grudnia 2004", $this->source());
		$this->assertContains("US$ 12,40", $this->source());
		$this->assertContains("46.412,42 €", $this->source());
		$this->assertContains("€100.00", $this->source());

	}
}
