<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TForm;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TBaseValidator;
use Prado\Web\UI\WebControls\TLuhnValidator;
use Prado\Web\UI\WebControls\TTextBox;

class TLuhnValidatorTest extends TestCase
{
	private TTextBox $input;
	private TLuhnValidator $validator;

	protected function setUp(): void
	{
		$page = new TPage();
		$this->input = new TTextBox();
		$this->input->setID('input');
		$this->validator = new TLuhnValidator();
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
	 * The cases shared with the client validator test, tests/js/validator/luhnvalidator.test.js.
	 */
	public static function caseProvider(): array
	{
		$cases = json_decode(file_get_contents(__DIR__ . '/../../../../js/validator/luhn-cases.json'), true);
		$data = [];
		foreach ($cases as $set => $list) {
			if ($set[0] === '_') {
				continue;
			}
			foreach ($list as $i => [$value, $expected]) {
				$data["$set #$i " . json_encode($value, JSON_UNESCAPED_UNICODE)] = [$set, $value, $expected];
			}
		}
		return $data;
	}

	/**
	 * @dataProvider caseProvider
	 */
	public function testSharedCases(string $set, string $value, bool $expected)
	{
		$this->validator->setAllowSeparators($set === 'separators');
		$this->assertSame($expected, $this->valid($value));
	}

	public function testEveryCheckDigit()
	{
		// Exactly one check digit completes a payload.
		$valid = array_filter(range(0, 9), fn ($d) => TLuhnValidator::isValidLuhn('7992739871' . $d));
		$this->assertSame([3], array_values($valid));
	}

	public function testSingleTranspositionIsCaught()
	{
		$this->assertTrue(TLuhnValidator::isValidLuhn('79927398713'));
		$this->assertFalse(TLuhnValidator::isValidLuhn('97927398713'));
	}

	public function testExtendsBaseValidator()
	{
		$this->assertInstanceOf(TBaseValidator::class, $this->validator);
		$this->assertSame('Prado.WebUI.TLuhnValidator', PradoUnit::invoke($this->validator, 'getClientClassName'));
	}

	public function testAllowSeparators()
	{
		$this->assertFalse($this->validator->getAllowSeparators());
		$this->validator->setAllowSeparators('true');
		$this->assertTrue($this->validator->getAllowSeparators());
	}

	public function testClientScriptOptions()
	{
		$this->validator->getPage()->setForm(new TForm());
		$this->validator->setAllowSeparators(true);
		$this->assertTrue(PradoUnit::invoke($this->validator, 'getClientScriptOptions')['AllowSeparators']);
	}
}
