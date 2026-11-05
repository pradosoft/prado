<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TForm;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TBaseValidator;
use Prado\Web\UI\WebControls\TIpAddressValidator;
use Prado\Web\UI\WebControls\TIpAddressVersion;
use Prado\Web\UI\WebControls\TTextBox;

class TIpAddressValidatorTest extends TestCase
{
	private TTextBox $input;
	private TIpAddressValidator $validator;

	protected function setUp(): void
	{
		$page = new TPage();
		$this->input = new TTextBox();
		$this->input->setID('input');
		$this->validator = new TIpAddressValidator();
		$this->validator->setControlToValidate('input');
		$page->getControls()->add($this->input);
		$page->getControls()->add($this->validator);
	}

	private function valid(string $value): bool
	{
		$this->input->setText($value);
		return PradoUnit::invoke($this->validator, 'evaluateIsValid');
	}

	private static function cases(): array
	{
		return json_decode(file_get_contents(__DIR__ . '/../../../../js/validator/ip-cases.json'), true);
	}

	private function applyOptions(string $set): void
	{
		foreach (self::cases()['options'][$set] as $property => $value) {
			$this->validator->{'set' . $property}($value);
		}
	}

	/**
	 * The cases shared with the client validator test, tests/js/validator/ipaddressvalidator.test.js.
	 */
	public static function caseProvider(): array
	{
		$data = [];
		foreach (self::cases() as $set => $list) {
			if ($set[0] === '_' || $set === 'options' || $set === 'clientRanges') {
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
		$this->applyOptions($set);
		$this->assertSame($expected, $this->valid($value));
	}

	/**
	 * The client test runs with the resolved ranges recorded in the shared cases.
	 */
	public function testSharedClientRanges()
	{
		$cases = self::cases();
		foreach ($cases['options'] as $set => $options) {
			$this->validator->setRanges($options['Ranges'] ?? '');
			$this->assertSame($cases['clientRanges'][$set] ?? [], $this->validator->getRangeList(), $set);
		}
	}

	public function testExtendsBaseValidator()
	{
		$this->assertInstanceOf(TBaseValidator::class, $this->validator);
		$this->assertSame('Prado.WebUI.TIpAddressValidator', PradoUnit::invoke($this->validator, 'getClientClassName'));
	}

	public function testDefaults()
	{
		$this->assertSame(TIpAddressVersion::Any, $this->validator->getVersion());
		$this->assertFalse($this->validator->getAllowCidr());
		$this->assertFalse($this->validator->getRequireCidr());
		$this->assertSame('', $this->validator->getRanges());
		$this->assertSame([], $this->validator->getRangeList());
	}

	public function testVersionIsAnEnum()
	{
		$this->validator->setVersion('IPv6');
		$this->assertSame(TIpAddressVersion::IPv6, $this->validator->getVersion());
		$this->expectException(TInvalidDataValueException::class);
		$this->validator->setVersion('IPv5');
	}

	public function testBooleanProperties()
	{
		$this->validator->setAllowCidr('true');
		$this->assertTrue($this->validator->getAllowCidr());
		$this->validator->setRequireCidr('true');
		$this->assertTrue($this->validator->getRequireCidr());
	}

	public function testRangeListTrimsAndIgnoresCase()
	{
		$this->validator->setRanges(' ! Loopback ,, 10.0.0.0/8 ,PUBLIC');
		$list = $this->validator->getRangeList();
		$this->assertCount(3, $list);
		$this->assertSame(['Negate' => true, 'Blocks' => TIpAddressValidator::NAMED_RANGES['loopback'], 'Outside' => false], $list[0]);
		$this->assertSame(['Negate' => false, 'Blocks' => ['10.0.0.0/8'], 'Outside' => false], $list[1]);
		$this->assertTrue($list[2]['Outside']);
		$this->assertNotContains('0.0.0.0/0', $list[2]['Blocks']);
	}

	public static function invalidRangeProvider(): array
	{
		return [['intranet'], ['10.0.0.0/33'], ['10.0.0'], ['!'], ['!!private']];
	}

	/**
	 * @dataProvider invalidRangeProvider
	 */
	public function testInvalidRangeThrows(string $range)
	{
		$this->validator->setRanges($range);
		$this->expectException(TConfigurationException::class);
		$this->validator->getRangeList();
	}

	public function testRangeEndingWithHostBitsMatchesItsNetwork()
	{
		$this->validator->setRanges('10.1.2.3/8');
		$this->assertTrue($this->valid('10.200.0.1'));
		$this->assertFalse($this->valid('11.0.0.1'));
	}

	public function testNamedRangesAreValidBlocks()
	{
		foreach (TIpAddressValidator::NAMED_RANGES as $name => $blocks) {
			foreach ($blocks as $block) {
				$this->assertNotNull(PradoUnit::invoke($this->validator, 'parseBlock', $block), "$name $block");
			}
		}
	}

	public function testPrefixMatches()
	{
		$this->assertTrue(TIpAddressValidator::prefixMatches(inet_pton('172.16.0.0'), inet_pton('172.31.255.255'), 12));
		$this->assertFalse(TIpAddressValidator::prefixMatches(inet_pton('172.16.0.0'), inet_pton('172.32.0.0'), 12));
		$this->assertTrue(TIpAddressValidator::prefixMatches(inet_pton('1.2.3.4'), inet_pton('5.6.7.8'), 0));
		$this->assertFalse(TIpAddressValidator::prefixMatches(inet_pton('1.2.3.4'), inet_pton('1.2.3.5'), 32));
	}

	public function testIsValidIpAddressIsPublic()
	{
		$this->assertTrue($this->validator->isValidIpAddress('192.0.2.1'));
		$this->assertFalse($this->validator->isValidIpAddress('not-an-ip'));
	}

	public function testClientScriptOptions()
	{
		$this->validator->getPage()->setForm(new TForm());
		$this->validator->setVersion(TIpAddressVersion::IPv4);
		$this->validator->setAllowCidr(true);
		$this->validator->setRequireCidr(true);
		$this->validator->setRanges('!10.1.0.0/16, private');
		$options = PradoUnit::invoke($this->validator, 'getClientScriptOptions');
		$this->assertSame('IPv4', $options['Version']);
		$this->assertTrue($options['AllowCidr']);
		$this->assertTrue($options['RequireCidr']);
		$this->assertSame($this->validator->getRangeList(), $options['Ranges']);
	}
}
