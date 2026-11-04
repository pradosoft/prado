<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TBaseValidator;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\WebControls\TUrlValidator;

class TUrlValidatorTest extends TestCase
{
	private TTextBox $input;
	private TUrlValidator $validator;

	protected function setUp(): void
	{
		$page = new TPage();
		$this->input = new TTextBox();
		$this->input->setID('input');
		$this->validator = new TUrlValidator();
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
	 * The cases shared with the client validator test, tests/js/validator/urlvalidator.test.js.
	 */
	public static function caseProvider(): array
	{
		$cases = json_decode(file_get_contents(__DIR__ . '/../../../../js/validator/url-cases.json'), true);
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
		match ($set) {
			'noTld' => $this->validator->setRequireTld(false),
			'noIdn' => $this->validator->setAllowIdn(false),
			'anyScheme' => $this->validator->setValidSchemes(''),
			'ftpOnly' => $this->validator->setValidSchemes('ftp'),
			default => null,
		};
		$this->assertSame($expected, $this->valid($value));
	}

	public function testExtendsBaseValidator()
	{
		$this->assertInstanceOf(TBaseValidator::class, $this->validator);
		$this->assertSame('Prado.WebUI.TUrlValidator', PradoUnit::invoke($this->validator, 'getClientClassName'));
	}

	public function testDefaults()
	{
		$this->assertSame('http,https', $this->validator->getValidSchemes());
		$this->assertSame(['http', 'https'], $this->validator->getValidSchemeList());
		$this->assertTrue($this->validator->getAllowIdn());
		$this->assertTrue($this->validator->getRequireTld());
	}

	public function testValidSchemeListTrimsLowersAndDedupes()
	{
		$this->validator->setValidSchemes(' HTTPS, ftp ,,https ');
		$this->assertSame(['https', 'ftp'], $this->validator->getValidSchemeList());
		$this->validator->setValidSchemes('');
		$this->assertSame([], $this->validator->getValidSchemeList());
	}

	public function testBooleanProperties()
	{
		$this->validator->setAllowIdn('false');
		$this->assertFalse($this->validator->getAllowIdn());
		$this->validator->setRequireTld('false');
		$this->assertFalse($this->validator->getRequireTld());
	}

	public function testDomainLengthLimit()
	{
		$label = str_repeat('a', 63);
		$host = implode('.', [$label, $label, $label, str_repeat('a', 57)]) . '.com';
		$this->assertSame(253, strlen($host));
		$this->assertTrue($this->valid("http://$host"));
		$this->assertFalse($this->valid("http://a$host"));
		$this->assertTrue($this->valid('http://' . str_repeat('a', 63) . '.com'));
	}

	public function testInvalidUtf8Fails()
	{
		$this->assertFalse($this->valid("http://example.com/\xff"));
		$this->assertFalse($this->valid("http://b\xc3cher.example"));
	}

	public function testIsValidUrlIsPublic()
	{
		$this->assertTrue($this->validator->isValidUrl('https://example.com'));
		$this->assertFalse($this->validator->isValidUrl('https://example'));
	}

	public function testClientScriptOptions()
	{
		$page = $this->validator->getPage();
		$page->setForm(new \Prado\Web\UI\TForm());
		$this->validator->setValidSchemes('HTTPS');
		$this->validator->setAllowIdn(false);
		$this->validator->setRequireTld(false);
		$options = PradoUnit::invoke($this->validator, 'getClientScriptOptions');
		$this->assertSame(['https'], $options['ValidSchemes']);
		$this->assertFalse($options['AllowIdn']);
		$this->assertFalse($options['RequireTld']);
	}
}
