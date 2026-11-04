<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TBaseValidator;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\WebControls\TUnicodeRestrictionLevel;
use Prado\Web\UI\WebControls\TUnicodeValidator;

class TUnicodeValidatorTest extends TestCase
{
	private TTextBox $input;
	private TUnicodeValidator $validator;

	protected function setUp(): void
	{
		$page = new TPage();
		$this->input = new TTextBox();
		$this->input->setID('input');
		$this->validator = new TUnicodeValidator();
		$this->validator->setControlToValidate('input');
		$page->getControls()->add($this->input);
		$page->getControls()->add($this->validator);
	}

	private function valid(string $value): bool
	{
		$this->input->setText($value);
		return PradoUnit::invoke($this->validator, 'evaluateIsValid');
	}

	public function testExtendsBaseValidator()
	{
		$this->assertInstanceOf(TBaseValidator::class, $this->validator);
		$this->assertSame('Prado.WebUI.TUnicodeValidator', PradoUnit::invoke($this->validator, 'getClientClassName'));
	}

	public function testDefaults()
	{
		$this->assertTrue($this->validator->getCheckInvisible());
		$this->assertTrue($this->validator->getCheckMixedNumbers());
		$this->assertTrue($this->validator->getCheckHiddenOverlay());
		$this->assertSame(TUnicodeRestrictionLevel::ModeratelyRestrictive, $this->validator->getRestrictionLevel());
		$this->assertSame('', $this->validator->getLocales());
		$this->assertSame([], $this->validator->getLocaleList());
	}

	public static function defaultProvider(): array
	{
		return [
			'empty' => ['', true],
			'ASCII' => ['admin', true],
			'space' => ['john smith', true],
			'Latin with accents' => ['José Müller', true],
			'Greek' => ['Ελληνικά', true],
			'Cyrillic' => ['Пользователь', true],
			'Japanese' => ['日本語テキスト', true],
			'Latin and Han' => ['abc日本', true],
			'Latin and Arabic' => ['abcعربي', true],
			'emoji' => ['hi😀', true],
			'Latin with a Cyrillic a' => ["p\u{0430}ypal", false],
			'Latin with a Greek omicron' => ["hell\u{03BF}", false],
			'zero-width space' => ["abc\u{200B}def", false],
			'zero-width joiner' => ["ab\u{200D}c", false],
			'right-to-left override' => ["file\u{202E}gpj.exe", false],
			'soft hyphen' => ["ad\u{00AD}min", false],
			'byte order mark' => ["\u{FEFF}admin", false],
			'tab' => ["ad\tmin", false],
			'newline' => ["ad\nmin", false],
			'Hangul filler' => ["\u{3164}", false],
			'combining grapheme joiner' => ["a\u{034F}b", false],
			'repeated nonspacing mark' => ["a\u{0301}\u{0301}", false],
			'mixed numbering systems' => ["\u{0661}2", false],
			'hidden overlay' => ["i\u{0307}", false],
			'invalid UTF-8' => ["ad\xffmin", false],
		];
	}

	/**
	 * @dataProvider defaultProvider
	 */
	public function testDefaultChecks(string $value, bool $expected)
	{
		$this->assertSame($expected, $this->valid($value));
	}

	public function testRestrictionLevels()
	{
		$this->validator->setRestrictionLevel(TUnicodeRestrictionLevel::Ascii);
		$this->assertTrue($this->valid('admin'));
		$this->assertFalse($this->valid('café'));

		$this->validator->setRestrictionLevel('singlescript');
		$this->assertSame(TUnicodeRestrictionLevel::SingleScript, $this->validator->getRestrictionLevel());
		$this->assertTrue($this->valid('café'));
		$this->assertFalse($this->valid('abc日本'));

		$this->validator->setRestrictionLevel(TUnicodeRestrictionLevel::HighlyRestrictive);
		$this->assertTrue($this->valid('abc日本'));
		$this->assertFalse($this->valid('abcعربي'));

		$this->validator->setRestrictionLevel(TUnicodeRestrictionLevel::MinimallyRestrictive);
		$this->assertTrue($this->valid("p\u{0430}ypal"));

		$this->validator->setRestrictionLevel(TUnicodeRestrictionLevel::Unrestricted);
		$this->assertTrue($this->valid("p\u{0430}ypal"));
		$this->assertFalse($this->valid("abc\u{200B}"));
	}

	public function testRestrictionLevelRejectsUnknownValue()
	{
		$this->expectException(TInvalidDataValueException::class);
		$this->validator->setRestrictionLevel('Strict');
	}

	public function testLocales()
	{
		$this->validator->setLocales(' en , ,ja ');
		$this->assertSame(['en', 'ja'], $this->validator->getLocaleList());
		$this->assertTrue($this->valid('hello'));
		$this->assertTrue($this->valid('日本語テキスト'));
		$this->assertFalse($this->valid('Ελληνικά'));
	}

	public function testChecksCanBeTurnedOff()
	{
		$this->validator->setCheckInvisible(false);
		$this->assertTrue($this->valid("abc\u{200B}def"));
		$this->assertTrue($this->valid("a\u{0301}\u{0301}"));

		$this->validator->setCheckMixedNumbers('false');
		$this->assertTrue($this->valid("\u{0661}2"));

		$this->validator->setCheckHiddenOverlay(false);
		$this->assertTrue($this->valid("i\u{0307}"));

		// Invalid UTF-8 fails whatever the checks.
		$this->validator->setRestrictionLevel(TUnicodeRestrictionLevel::Unrestricted);
		$this->assertFalse($this->valid("\xff"));
		$this->assertTrue($this->valid("p\u{0430}ypal"));
	}

	public function testNoSpoofcheckerWhenEveryCheckIsOff()
	{
		$this->validator->setCheckInvisible(false);
		$this->validator->setCheckMixedNumbers(false);
		$this->validator->setCheckHiddenOverlay(false);
		$this->validator->setRestrictionLevel(TUnicodeRestrictionLevel::Unrestricted);
		$this->assertNull(PradoUnit::invoke($this->validator, 'createSpoofchecker'));
		$this->assertTrue($this->valid("p\u{0430}ypal\u{200B}"));
	}

	public function testServerFailureMarksAriaInvalid()
	{
		$this->input->setText("p\u{0430}ypal");
		$this->assertFalse($this->validator->validate());
		PradoUnit::invoke($this->validator, 'updateControlAria');
		$this->assertSame('true', $this->input->getAttributes()->itemAt('aria-invalid'));

		// A later valid request removes the attribute kept in the view state.
		$this->input->setIsValid(true);
		$this->input->setText('paypal');
		$this->assertTrue($this->validator->validate());
		PradoUnit::invoke($this->validator, 'updateControlAria');
		$this->assertFalse($this->input->getAttributes()->contains('aria-invalid'));
	}

	public function testValidControlGetsNoAriaAttribute()
	{
		$this->input->setText('paypal');
		$this->validator->validate();
		PradoUnit::invoke($this->validator, 'updateControlAria');
		$this->assertFalse($this->input->getHasAttributes() && $this->input->getAttributes()->contains('aria-invalid'));
	}

	public function testFailedValidatorTellsTheClient()
	{
		$this->validator->getPage()->setForm(new \Prado\Web\UI\TForm());
		$this->assertArrayNotHasKey('IsValid', PradoUnit::invoke($this->validator, 'getClientScriptOptions'));
		$this->input->setText("p\u{0430}ypal");
		$this->validator->validate();
		$this->assertFalse(PradoUnit::invoke($this->validator, 'getClientScriptOptions')['IsValid']);
	}

	public function testIsSuspiciousIsPublic()
	{
		$this->assertFalse($this->validator->isSuspicious('admin'));
		$this->assertTrue($this->validator->isSuspicious("p\u{0430}ypal"));
	}
}
