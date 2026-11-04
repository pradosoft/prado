<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TForm;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TBaseValidator;
use Prado\Web\UI\WebControls\TJsonValidator;
use Prado\Web\UI\WebControls\TTextBox;

class TJsonValidatorTest extends TestCase
{
	private TTextBox $input;
	private TJsonValidator $validator;

	protected function setUp(): void
	{
		$page = new TPage();
		$this->input = new TTextBox();
		$this->input->setID('input');
		$this->validator = new TJsonValidator();
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
	 * The cases shared with the client validator test, tests/js/validator/jsonvalidator.test.js.
	 */
	public static function caseProvider(): array
	{
		$cases = json_decode(file_get_contents(__DIR__ . '/../../../../js/validator/json-cases.json'), true);
		$data = [];
		foreach ($cases['cases'] as $i => [$value, $expected]) {
			$data["#$i " . json_encode($value)] = [$value, $expected];
		}
		return $data;
	}

	/**
	 * @dataProvider caseProvider
	 */
	public function testSharedCases(string $value, bool $expected)
	{
		$this->assertSame($expected, $this->valid($value));
	}

	public function testNestingDepthLimit()
	{
		$depth = TJsonValidator::MAX_DEPTH - 1;
		$this->assertTrue($this->valid(str_repeat('[', $depth) . str_repeat(']', $depth)));
		$this->assertFalse($this->valid(str_repeat('[', $depth + 1) . str_repeat(']', $depth + 1)));
		$this->assertTrue($this->valid(str_repeat('{"a":', $depth) . '1' . str_repeat('}', $depth)));
		$this->assertFalse($this->valid(str_repeat('{"a":', $depth + 1) . '1' . str_repeat('}', $depth + 1)));
	}

	public function testInvalidUtf8Fails()
	{
		$this->assertFalse($this->valid("\"\xff\""));
	}

	public function testExtendsBaseValidator()
	{
		$this->assertInstanceOf(TBaseValidator::class, $this->validator);
		$this->assertSame('Prado.WebUI.TJsonValidator', PradoUnit::invoke($this->validator, 'getClientClassName'));
	}

	public function testIsValidJsonIsPublic()
	{
		$this->assertTrue($this->validator->isValidJson('{"a":[1]}'));
		$this->assertFalse($this->validator->isValidJson('{"a":[1}'));
	}

	public function testClientScriptOptions()
	{
		$this->validator->getPage()->setForm(new TForm());
		$this->assertSame(TJsonValidator::MAX_DEPTH, PradoUnit::invoke($this->validator, 'getClientScriptOptions')['MaxDepth']);
	}
}
