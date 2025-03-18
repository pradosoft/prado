<?php

//New Test
class QuickstartI18NTestCase extends \Prado\Tests\PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?notheme=true&page=Advanced.Samples.I18N.Home&amp;lang=en&amp;notheme=true");
		$this->assertSourceContains("Internationlization in PRADO");
		$this->assertContains("46.412,42 €", $this->source());
		$this->assertSourceContains("$12.40");
		$this->assertSourceContains("€100.00");
		$this->assertContains("December 6, 2004", $this->source());
		$this->url("quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=zh&amp;notheme=true");
		$this->assertSourceContains("PRADO 国际化");
		$this->assertSourceContains("2004 十二月");
		$this->assertSourceContains("US$ 12.40");
		$this->assertContains("46.412,42 €", $this->source());
		$this->assertSourceContains("€100.00 ");
		$this->url("quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=zh_TW&amp;notheme=true");
		$this->assertSourceContains("PRADO 國際化");
		$this->assertSourceContains("2004年12月6日");
		$this->assertSourceContains("US$12.40");
		$this->assertContains("46.412,42 €", $this->source());
		$this->assertSourceContains("€100.00");
		$this->url("quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=de&amp;notheme=true");
		$this->assertSourceContains("Internationalisierung in PRADO");
		$this->assertSourceContains("6. Dezember 2004 ");
		$this->assertContains("$ 12,40", $this->source());
		$this->assertContains("46.412,42 €", $this->source());
		$this->assertSourceContains("€100.00");
		$this->url("quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=es&amp;notheme=true");
		$this->assertSourceContains("Internationlization en PRADO");
		$this->assertSourceContains("6 de diciembre de 2004");
		$this->assertSourceContains("US$12.40");
		$this->assertContains("46.412,42 €", $this->source());
		$this->assertSourceContains("€100.00");
		$this->url("quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=fr&amp;notheme=true");
		$this->assertSourceContains("Internationalisation avec PRADO");
		$this->assertSourceContains("6 décembre 2004");
		$this->assertContains("12,40 $", $this->source());
		$this->assertContains("46.412,42 €", $this->source());
		$this->assertSourceContains("€100.00");
		$this->url("quickstart/index.php?page=Advanced.Samples.I18N.Home&amp;lang=pl&amp;notheme=true");
		$this->assertSourceContains("Internacjonalizacja w PRADO");
		$this->assertSourceContains("6 grudnia 2004");
		$this->assertContains("US$ 12,40", $this->source());
		$this->assertContains("46.412,42 €", $this->source());
		$this->assertSourceContains("€100.00");
	}
}
