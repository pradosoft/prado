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
		$this->assertFalse($this->validator->getCheckDns());
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
		$this->validator->setCheckDns('true');
		$this->assertTrue($this->validator->getCheckDns());
	}

	/**
	 * Replaces the validator with one, with CheckDns, whose DNS lookup finds only the given domains.
	 * @param string[] $domains the ASCII domains that have a record.
	 * @return TUrlValidatorDnsStub the validator under test.
	 */
	private function stubDns(array $domains): TUrlValidatorDnsStub
	{
		$stub = new TUrlValidatorDnsStub();
		$stub->domains = $domains;
		$stub->setControlToValidate('input');
		$stub->setCheckDns(true);
		$this->validator->getParent()->getControls()->add($stub);
		$this->validator = $stub;
		return $stub;
	}

	public function testCheckDnsLooksUpDomainHosts()
	{
		$stub = $this->stubDns(['example.com', 'xn--bcher-kva.example']);
		$this->assertTrue($this->valid('https://Example.COM/path'));
		$this->assertTrue($this->valid('http://bücher.example'));
		$this->assertFalse($this->valid('https://sanoehusnhoeustnhatoehut.com'));
		$this->assertSame(['example.com', 'xn--bcher-kva.example', 'sanoehusnhoeustnhatoehut.com'], $stub->lookups);
	}

	public function testCheckDnsSkipsIpHostsAndInvalidUrls()
	{
		$stub = $this->stubDns([]);
		$this->assertTrue($this->valid('http://192.0.2.1/'));
		$this->assertTrue($this->valid('http://[2001:db8::1]/'));
		$this->assertFalse($this->valid('ftp://example.com'));
		$this->assertFalse($this->valid('http://localhost'));
		$this->assertTrue($this->valid(''));
		$this->assertSame([], $stub->lookups);
	}

	public function testCheckDnsOffMakesNoLookup()
	{
		$stub = $this->stubDns([]);
		$stub->setCheckDns(false);
		$this->assertTrue($this->valid('https://sanoehusnhoeustnhatoehut.com'));
		$this->assertSame([], $stub->lookups);
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
		$this->validator->setCheckDns(true);
		$this->assertArrayNotHasKey('CheckDns', PradoUnit::invoke($this->validator, 'getClientScriptOptions'));
	}
}

/**
 * A TUrlValidator whose DNS lookup finds only {@see $domains} and records each lookup.
 */
class TUrlValidatorDnsStub extends TUrlValidator
{
	public array $domains = [];
	public array $lookups = [];

	protected function lookupDns(string $domain): bool
	{
		$this->lookups[] = $domain;
		return in_array($domain, $this->domains, true);
	}
}
