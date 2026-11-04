<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TForm;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TBaseValidator;
use Prado\Web\UI\WebControls\TIbanValidator;
use Prado\Web\UI\WebControls\TTextBox;

class TIbanValidatorTest extends TestCase
{
	private TTextBox $input;
	private TIbanValidator $validator;

	protected function setUp(): void
	{
		$page = new TPage();
		$this->input = new TTextBox();
		$this->input->setID('input');
		$this->validator = new TIbanValidator();
		$this->validator->setControlToValidate('input');
		$page->getControls()->add($this->input);
		$page->getControls()->add($this->validator);
	}

	private function valid(string $value): bool
	{
		$this->input->setText($value);
		return PradoUnit::invoke($this->validator, 'evaluateIsValid');
	}

	/**
	 * The cases shared with the client validator test, tests/js/validator/ibanvalidator.test.js.
	 */
	public static function caseProvider(): array
	{
		$cases = json_decode(file_get_contents(__DIR__ . '/../../../../js/validator/iban-cases.json'), true);
		$data = [];
		foreach ($cases as $set => $list) {
			if ($set[0] === '_') {
				continue;
			}
			foreach ($list as $i => [$value, $expected]) {
				$data["$set #$i " . json_encode($value)] = [$set, $value, $expected];
			}
		}
		return $data;
	}

	/**
	 * @dataProvider caseProvider
	 */
	public function testSharedCases(string $set, string $value, bool $expected)
	{
		$this->validator->setCountries($set === 'dach' ? 'DE,AT,CH' : '');
		$this->assertSame($expected, $this->valid($value));
	}

	public function testCountryFormatsAreConsistent()
	{
		$this->assertCount(89, TIbanValidator::COUNTRY_FORMATS);
		foreach (TIbanValidator::COUNTRY_FORMATS as $code => $format) {
			$this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $code);
			$this->assertMatchesRegularExpression('/^([0-9]+[anc],)*[0-9]+[anc]$/', $format, $code);
		}
		// Spot checks against the SWIFT IBAN Registry lengths.
		$length = fn ($code) => 4 + array_sum(array_map('intval', explode(',', TIbanValidator::COUNTRY_FORMATS[$code])));
		$this->assertSame(22, $length('DE'));
		$this->assertSame(15, $length('NO'));
		$this->assertSame(27, $length('FR'));
		$this->assertSame(32, $length('LC'));
		$this->assertSame(33, $length('RU'));
	}

	public function testMod97()
	{
		$this->assertSame(1, TIbanValidator::mod97('370400440532013000DE89'));
		$this->assertSame(0, TIbanValidator::mod97('97'));
		$this->assertSame(10, TIbanValidator::mod97('A'));
		$this->assertSame(35, TIbanValidator::mod97('Z'));
	}

	public function testBbanPattern()
	{
		$this->assertSame('/^[A-Z]{4}[0-9]{6}[A-Z0-9]{8}$/', TIbanValidator::bbanPattern('4a,6n,8c'));
	}

	public function testCountries()
	{
		$this->assertSame('', $this->validator->getCountries());
		$this->assertSame(TIbanValidator::COUNTRY_FORMATS, $this->validator->getCountryFormats());
		$this->validator->setCountries(' de, at ,XX,,');
		$this->assertSame(['AT' => '16n', 'DE' => '18n'], $this->validator->getCountryFormats());
	}

	public function testExtendsBaseValidator()
	{
		$this->assertInstanceOf(TBaseValidator::class, $this->validator);
		$this->assertSame('Prado.WebUI.TIbanValidator', PradoUnit::invoke($this->validator, 'getClientClassName'));
	}

	public function testIsValidIbanIsPublic()
	{
		$this->assertTrue($this->validator->isValidIban('GB82WEST12345698765432'));
		$this->assertFalse($this->validator->isValidIban('GB82WEST12345698765433'));
	}

	public function testClientScriptOptions()
	{
		$this->validator->getPage()->setForm(new TForm());
		$this->validator->setCountries('CH');
		$this->assertSame(['CH' => '5n,12c'], PradoUnit::invoke($this->validator, 'getClientScriptOptions')['CountryFormats']);
	}
}
