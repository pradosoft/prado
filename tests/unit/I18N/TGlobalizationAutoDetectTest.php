<?php

use PHPUnit\Framework\TestCase;
use Prado\I18N\TGlobalizationAutoDetect;

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
}

class TGlobalizationAutoDetectTest extends TestCase
{
	private TGlobalizationAutoDetectTestable $autoDetect;

	protected function setUp(): void
	{
		$this->autoDetect = new TGlobalizationAutoDetectTestable();
	}

	protected function tearDown(): void
	{
		// Restore Accept-Language so other tests are not affected.
		unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
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
}
