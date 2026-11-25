<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TCheckBox;
use Prado\Web\UI\WebControls\TEmailAddressValidator;
use Prado\Web\UI\WebControls\TTextBox;

/**
 * An email address validator that looks up MX records in a list.
 */
class TEmailAddressValidatorTestValidator extends TEmailAddressValidator
{
	/** @var string[] the domains that have an MX record. */
	public array $mxDomains = ['example.com'];
	/** @var string[] the domains looked up. */
	public array $lookups = [];

	protected function hasMxRecord(string $domain): bool
	{
		$this->lookups[] = $domain;
		return in_array($domain, $this->mxDomains, true);
	}
}

class TEmailAddressValidatorTest extends TestCase
{
	private TTextBox $box;

	/**
	 * Builds a page with a text box and an MX-listing validator that validates it.
	 */
	private function createValidator(): TEmailAddressValidatorTestValidator
	{
		$page = new TPage();
		$this->box = new TTextBox();
		$this->box->setID('Box');
		$page->getControls()->add($this->box);
		$validator = new TEmailAddressValidatorTestValidator();
		$validator->setID('Valid');
		$validator->setControlToValidate('Box');
		$page->getControls()->add($validator);
		return $validator;
	}

	private function isValid(TEmailAddressValidator $validator, string $value): bool
	{
		$this->box->setText($value);
		return $validator->evaluateIsValid();
	}

	public function testDefaults()
	{
		$validator = new TEmailAddressValidator();
		$this->assertFalse($validator->getCheckMXRecord());
		$this->assertSame(TEmailAddressValidator::EMAIL_REGEXP, $validator->getRegularExpression());
		$this->assertSame('Prado.WebUI.TEmailAddressValidator', PradoUnit::invoke($validator, 'getClientClassName'));

		$validator->setCheckMXRecord('true');
		$this->assertTrue($validator->getCheckMXRecord());
		$validator->setRegularExpression('.+@.+');
		$this->assertSame('.+@.+', $validator->getRegularExpression());
	}

	public static function provideAddresses(): array
	{
		return [
			'empty' => ['', true],
			'address' => ['user.name+tag@mail.example.com', true],
			'no domain' => ['user@', false],
			'no at sign' => ['user.example.com', false],
			'254 bytes' => [str_repeat('a', 64) . '@' . str_repeat('b', 63) . '.' . str_repeat('c', 63) . '.' . str_repeat('d', 61), true],
			'255 bytes' => [str_repeat('a', 64) . '@' . str_repeat('b', 63) . '.' . str_repeat('c', 63) . '.' . str_repeat('d', 62), false],
		];
	}

	/**
	 * @param string $value the input.
	 * @param bool $expected whether the input is valid.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provideAddresses')]
	public function testAddress(string $value, bool $expected)
	{
		$validator = $this->createValidator();
		$this->assertSame($expected, $this->isValid($validator, $value));
		$this->assertSame([], $validator->lookups, 'MX records are checked only when CheckMXRecord is true.');
	}

	public function testValueThatIsNotAStringIsInvalid()
	{
		$page = new TPage();
		$box = new TCheckBox();
		$box->setID('Box');
		$page->getControls()->add($box);
		$validator = new TEmailAddressValidator();
		$validator->setControlToValidate('Box');
		$page->getControls()->add($validator);
		$this->assertFalse($validator->evaluateIsValid());
	}

	public function testCheckMXRecordLooksUpTheDomain()
	{
		$validator = $this->createValidator();
		$validator->setCheckMXRecord(true);
		$this->assertTrue($this->isValid($validator, 'user@example.com'));
		$this->assertFalse($this->isValid($validator, 'user@example.org'));
		$this->assertFalse($this->isValid($validator, 'not an address'));
		$this->assertTrue($this->isValid($validator, ''));
		$this->assertSame(['example.com', 'example.org'], $validator->lookups, 'An empty or malformed address is not looked up.');
	}

	public function testCheckMXRecordUsesTheDomainAfterTheLastAtSign()
	{
		$validator = $this->createValidator();
		$validator->setCheckMXRecord(true);
		$validator->setRegularExpression('.+');
		$this->assertTrue($this->isValid($validator, '"a@b"@example.com'));
		$this->assertFalse($this->isValid($validator, 'no-at-sign'));
		$this->assertFalse($this->isValid($validator, 'user@'));
		$this->assertSame(['example.com'], $validator->lookups);
	}

	public function testHasMxRecordAsksTheResolver()
	{
		$this->assertFalse(PradoUnit::invoke(new TEmailAddressValidator(), 'hasMxRecord', 'mx.invalid'), 'The .invalid top-level domain has no records.');
	}
}
