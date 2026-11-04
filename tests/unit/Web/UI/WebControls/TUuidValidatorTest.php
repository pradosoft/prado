<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TForm;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TBaseValidator;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\WebControls\TUuidValidator;

class TUuidValidatorTest extends TestCase
{
	private TTextBox $input;
	private TUuidValidator $validator;

	protected function setUp(): void
	{
		$page = new TPage();
		$this->input = new TTextBox();
		$this->input->setID('input');
		$this->validator = new TUuidValidator();
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
	 * The cases shared with the client validator test, tests/js/validator/uuidvalidator.test.js.
	 */
	public static function caseProvider(): array
	{
		$cases = json_decode(file_get_contents(__DIR__ . '/../../../../js/validator/uuid-cases.json'), true);
		$data = [];
		foreach ($cases as $set => $list) {
			if ($set[0] === '_') {
				continue;
			}
			foreach ($list as $i => [$value, $expected]) {
				$data["$set #$i $value"] = [$set, $value, $expected];
			}
		}
		return $data;
	}

	/**
	 * @dataProvider caseProvider
	 */
	public function testSharedCases(string $set, string $value, bool $expected)
	{
		match ($set) {
			'loose' => $this->validator->setStrict(false),
			'v4v7' => $this->validator->setVersions('4,7'),
			'nil' => $this->validator->setAllowNil(true),
			default => null,
		};
		$this->assertSame($expected, $this->valid($value));
	}

	public function testExtendsBaseValidator()
	{
		$this->assertInstanceOf(TBaseValidator::class, $this->validator);
		$this->assertSame('Prado.WebUI.TUuidValidator', PradoUnit::invoke($this->validator, 'getClientClassName'));
	}

	public function testDefaults()
	{
		$this->assertSame('', $this->validator->getVersions());
		$this->assertSame([], $this->validator->getVersionList());
		$this->assertTrue($this->validator->getStrict());
		$this->assertFalse($this->validator->getAllowNil());
	}

	public function testVersionListDropsInvalidAndDuplicateVersions()
	{
		$this->validator->setVersions(' 7, 4 ,0,9,x,,4');
		$this->assertSame([7, 4], $this->validator->getVersionList());
	}

	public function testBooleanProperties()
	{
		$this->validator->setStrict('false');
		$this->assertFalse($this->validator->getStrict());
		$this->validator->setAllowNil('true');
		$this->assertTrue($this->validator->getAllowNil());
	}

	public function testIsValidUuidIsPublic()
	{
		$this->assertTrue($this->validator->isValidUuid('550e8400-e29b-41d4-a716-446655440000'));
		$this->assertFalse($this->validator->isValidUuid('not-a-uuid'));
	}

	public function testClientScriptOptions()
	{
		$this->validator->getPage()->setForm(new TForm());
		$this->validator->setVersions('4,7');
		$this->validator->setStrict(false);
		$this->validator->setAllowNil(true);
		$options = PradoUnit::invoke($this->validator, 'getClientScriptOptions');
		$this->assertSame([4, 7], $options['Versions']);
		$this->assertFalse($options['Strict']);
		$this->assertTrue($options['AllowNil']);
	}
}
