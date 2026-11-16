<?php

namespace Prado\Test\Unit\I18N;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Prado\I18N\TGlobalizationAutoDetect;
use Prado\Prado;
use Prado\Test\Unit\Harness\TTestApplication;
use Prado\Test\Unit\PradoUnit;

/**
 * Exposes the protected getIsValidLocale() method for unit testing.
 */
class TGlobalizationAutoDetectTestable extends TGlobalizationAutoDetect
{
	public function isValidLocale(string $locale): bool
	{
		return $this->getIsValidLocale($locale);
	}

	/** Reset the language cache so each test starts clean. */
	public function resetLanguages(): void
	{
		$this->languages = null;
	}

	/** Expose the protected getLanguages() for unit testing. */
	public function getLanguages(): array
	{
		return parent::getLanguages();
	}

	/** Expose the protected getCharsets() for unit testing. */
	public function getCharsets(): array
	{
		return parent::getCharsets();
	}
}

class TGlobalizationAutoDetectTest extends TestCase
{
	private TGlobalizationAutoDetectTestable $autoDetect;

	private array $_appSnapshot;

	private array $_serverSnapshot;

	protected function setUp(): void
	{
		$this->autoDetect = new TGlobalizationAutoDetectTestable();
		$this->_appSnapshot = TTestApplication::snapshotApp(null, ['_globalization']);
		$this->_serverSnapshot = array_intersect_key($_SERVER, ['HTTP_ACCEPT_LANGUAGE' => 1, 'HTTP_ACCEPT_CHARSET' => 1]);
	}

	protected function tearDown(): void
	{
		// Restore Accept-Language and Accept-Charset so other tests are not affected.
		unset($_SERVER['HTTP_ACCEPT_LANGUAGE'], $_SERVER['HTTP_ACCEPT_CHARSET']);
		$_SERVER = $this->_serverSnapshot + $_SERVER;
		TTestApplication::restoreApp($this->_appSnapshot);
	}

	// ── getIsValidLocale ────────────────────────────────────────────────────

	/**
	 * PRADO's POSIX underscore form must be accepted regardless of what
	 * format ResourceBundle::getLocales() uses internally.  getIsValidLocale()
	 * tries all three match directions (direct, POSIX→BCP 47, BCP 47→POSIX).
	 */
	public function testGetIsValidLocaleAcceptsPosixUnderscoreForm()
	{
		$this->assertTrue($this->autoDetect->isValidLocale('en_US'));
		$this->assertTrue($this->autoDetect->isValidLocale('fr_FR'));
		$this->assertTrue($this->autoDetect->isValidLocale('de_DE'));
	}

	/**
	 * BCP 47 hyphen form must also be accepted.  ResourceBundle::getLocales()
	 * stores some locales in BCP 47 form and some in POSIX form depending on
	 * ICU version; getIsValidLocale() bridges both directions so that either
	 * form can be validated successfully.
	 */
	public function testGetIsValidLocaleAcceptsBcp47HyphenForm()
	{
		$this->assertTrue($this->autoDetect->isValidLocale('en-US'));
		$this->assertTrue($this->autoDetect->isValidLocale('fr-FR'));
		$this->assertTrue($this->autoDetect->isValidLocale('de-DE'));
	}

	public function testGetIsValidLocaleRejectsInvalidLocale()
	{
		$this->assertFalse($this->autoDetect->isValidLocale('invalid_locale'));
		$this->assertFalse($this->autoDetect->isValidLocale('xx_YY'));
	}

	// ── getLanguages ────────────────────────────────────────────────────────

	/**
	 * Browser Accept-Language headers are BCP 47 (hyphens).  getLanguages()
	 * must convert them to POSIX underscore form before validating, and the
	 * result must be in POSIX form for the rest of the framework.
	 */
	public function testGetLanguagesConvertsBcp47ToPosixForm()
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de-DE';
		$this->autoDetect->resetLanguages();

		$languages = $this->autoDetect->getLanguages();

		$this->assertContains('de_DE', $languages);
		$this->assertNotContains('de-DE', $languages);
	}

	public function testGetLanguagesAcceptsSimpleLanguageCode()
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr';
		$this->autoDetect->resetLanguages();

		$this->assertContains('fr', $this->autoDetect->getLanguages());
	}

	public function testGetLanguagesIgnoresQValues()
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9,fr;q=0.8';
		$this->autoDetect->resetLanguages();

		$languages = $this->autoDetect->getLanguages();

		$this->assertContains('en_US', $languages);
		$this->assertContains('en', $languages);
		$this->assertContains('fr', $languages);
	}

	public function testGetLanguagesReturnsEmptyArrayWhenHeaderAbsent()
	{
		unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
		$this->autoDetect->resetLanguages();

		$this->assertSame([], $this->autoDetect->getLanguages());
	}

	public function testGetLanguagesTrimsTheListSeparatorSpace()
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US, fr-CA , de';
		$this->assertSame(['en_US', 'fr_CA', 'de'], $this->autoDetect->getLanguages());
	}

	public function testGetLanguagesTakesTheSubtagOfAnIPrefix()
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'i-de,i-klingon';
		$this->assertSame(['de'], $this->autoDetect->getLanguages());
	}

	public function testGetLanguagesIsComputedOnce()
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr';
		$this->assertSame(['fr'], $this->autoDetect->getLanguages());
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de';
		$this->assertSame(['fr'], $this->autoDetect->getLanguages());
	}

	// ── getCharsets ─────────────────────────────────────────────────────────

	public function testGetCharsetsIsEmptyWithoutTheHeader()
	{
		$this->assertSame([], $this->autoDetect->getCharsets());
	}

	public function testGetCharsetsRemovesQValuesSpacesAndEmptyEntries()
	{
		$_SERVER['HTTP_ACCEPT_CHARSET'] = 'utf-8, iso-8859-1;q=0.5,, ;q=0.1';
		$this->assertSame(['utf-8', 'iso-8859-1'], $this->autoDetect->getCharsets());
		$_SERVER['HTTP_ACCEPT_CHARSET'] = 'utf-16';
		$this->assertSame(['utf-8', 'iso-8859-1'], $this->autoDetect->getCharsets());
	}

	// ── AvailableLanguages ──────────────────────────────────────────────────

	public function testAvailableLanguagesIsATrimmedList()
	{
		$this->assertNull($this->autoDetect->getAvailableLanguages());
		$this->autoDetect->setAvailableLanguages('en, fr_FR ,de');
		$this->assertSame(['en', 'fr_FR', 'de'], $this->autoDetect->getAvailableLanguages());
	}

	// ── init ────────────────────────────────────────────────────────────────

	public function testInitSetsTheCultureToTheFirstBrowserLanguage()
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,en;q=0.5';
		$this->autoDetect->init(null);
		$this->assertSame('fr_FR', $this->autoDetect->getDetectedLanguage());
		$this->assertSame('fr_FR', $this->autoDetect->getCulture());
		$this->assertSame($this->autoDetect, Prado::getApplication()->getGlobalization(false));
	}

	public function testInitSkipsTheLanguagesThatAreNotAvailable()
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,de;q=0.8,en;q=0.5';
		$this->autoDetect->setAvailableLanguages('en,de');
		$this->autoDetect->init(null);
		$this->assertSame('de', $this->autoDetect->getDetectedLanguage());
		$this->assertSame('de', $this->autoDetect->getCulture());
	}

	public function testInitWithAnEmptyAvailableListAcceptsAnyLanguage()
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr';
		PradoUnit::setProp($this->autoDetect, 'validLanguages', []);
		$this->autoDetect->init(null);
		$this->assertSame('fr', $this->autoDetect->getDetectedLanguage());
	}

	public function testInitWithoutAMatchKeepsTheDefaultCulture()
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR';
		$this->autoDetect->setAvailableLanguages('en');
		$this->autoDetect->setDefaultCulture('en_GB');
		$this->autoDetect->init(null);
		$this->assertNull($this->autoDetect->getDetectedLanguage());
		$this->assertSame('en_GB', $this->autoDetect->getCulture());
	}

	public function testInitWithoutTheHeaderKeepsTheDefaultCulture()
	{
		$this->autoDetect->init(null);
		$this->assertNull($this->autoDetect->getDetectedLanguage());
		$this->assertSame('en', $this->autoDetect->getCulture());
	}

	/**
	 * The locale list is read on the first call in a process; a BCP 47 locale matches its POSIX form.
	 */
	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testGetIsValidLocaleReadsTheLocalesOnTheFirstCall()
	{
		$this->assertTrue($this->autoDetect->isValidLocale('en-US'));
		$this->assertTrue($this->autoDetect->isValidLocale('en_US'));
	}

	public function testGetCharsetsOfOneCharset()
	{
		$_SERVER['HTTP_ACCEPT_CHARSET'] = 'utf-8';
		$this->assertSame(['utf-8'], $this->autoDetect->getCharsets());
	}

	public function testGetCharsetsOfOneEmptyEntry()
	{
		$_SERVER['HTTP_ACCEPT_CHARSET'] = ' ';
		$this->assertSame([], $this->autoDetect->getCharsets());
	}

	public function testInitWithOneAvailableBrowserLanguage()
	{
		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de';
		$this->autoDetect->setAvailableLanguages('en,de');
		$this->autoDetect->init(null);
		$this->assertSame('de', $this->autoDetect->getDetectedLanguage());
	}
}
