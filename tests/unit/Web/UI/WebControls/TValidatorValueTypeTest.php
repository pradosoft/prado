<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Web\UI\IValidatable;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TBaseValidator;
use Prado\Web\UI\WebControls\TIbanValidator;
use Prado\Web\UI\WebControls\TIpAddressValidator;
use Prado\Web\UI\WebControls\TJsonValidator;
use Prado\Web\UI\WebControls\TLuhnValidator;
use Prado\Web\UI\WebControls\TPasswordStrengthValidator;
use Prado\Web\UI\WebControls\TUnicodeValidator;
use Prado\Web\UI\WebControls\TUrlValidator;
use Prado\Web\UI\WebControls\TUuidValidator;

/**
 * A validatable control whose validation value the test sets.
 */
class TValidatorValueTypeTestControl extends TControl implements IValidatable
{
	public mixed $value = '';
	private bool $_isValid = true;

	public function getValidationPropertyValue()
	{
		return $this->value;
	}

	public function getIsValid()
	{
		return $this->_isValid;
	}

	public function setIsValid($value)
	{
		$this->_isValid = (bool) $value;
	}
}

/**
 * The validators of a text format treat a null value as empty and fail any
 * other value that is not a string.
 */
class TValidatorValueTypeTest extends TestCase
{
	public static function provideValues(): array
	{
		$validators = [
			TIbanValidator::class,
			TIpAddressValidator::class,
			TJsonValidator::class,
			TLuhnValidator::class,
			TPasswordStrengthValidator::class,
			TUnicodeValidator::class,
			TUrlValidator::class,
			TUuidValidator::class,
		];
		$data = [];
		foreach ($validators as $class) {
			$name = substr($class, strrpos($class, '\\') + 1);
			$data["$name, null"] = [$class, null, true];
			$data["$name, true"] = [$class, true, false];
			$data["$name, integer"] = [$class, 42, false];
			$data["$name, array"] = [$class, ['a'], false];
		}
		return $data;
	}

	/**
	 * @param string $class the validator class.
	 * @param mixed $value the validation value.
	 * @param bool $expected whether the value is valid.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('provideValues')]
	public function testValueType(string $class, mixed $value, bool $expected)
	{
		$page = new TPage();
		$control = new TValidatorValueTypeTestControl();
		$control->setID('Input');
		$control->value = $value;
		$page->getControls()->add($control);
		/** @var TBaseValidator $validator */
		$validator = new $class();
		$validator->setControlToValidate('Input');
		$page->getControls()->add($validator);

		$this->assertSame($expected, $validator->validate());
		$this->assertSame($expected, $control->getIsValid());
	}
}
