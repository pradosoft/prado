<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TEmailAddressValidator;
use Prado\Web\UI\WebControls\TRegularExpressionValidator;
use Prado\Web\UI\WebControls\TTextBox;

class TRegularExpressionValidatorTest extends TestCase
{
	private TTextBox $box;

	/**
	 * Builds a page with a text box and a validator of $class that validates it.
	 */
	private function createValidator(string $expression, string $class = TRegularExpressionValidator::class): TRegularExpressionValidator
	{
		$page = new TPage();
		$this->box = new TTextBox();
		$this->box->setID('Box');
		$page->getControls()->add($this->box);
		$validator = new $class();
		$validator->setID('Valid');
		$validator->setControlToValidate('Box');
		$validator->setRegularExpression($expression);
		$page->getControls()->add($validator);
		return $validator;
	}

	private function isValid(TRegularExpressionValidator $validator, string $value): bool
	{
		$this->box->setText($value);
		return $validator->evaluateIsValid();
	}

	public static function provideMatches(): array
	{
		return [
			'whole value' => ['\d+', '12345', true],
			'partial value' => ['\d+', '123abc', false],
			'alternation, first' => ['\d{18}|\d{15}', '123456789012345678', true],
			'alternation, second' => ['\d{18}|\d{15}', '123456789012345', true],
			'alternation, prefix' => ['\d{18}|\d{15}', '123456789012345678xyz', false],
			'alternation, suffix' => ['abc|x', 'zzzx', false],
			'alternation, longer second' => ['a|ab', 'ab', true],
			'trailing newline' => ['abc', "abc\n", false],
			'unescaped slash' => ['a/b', 'a/b', true],
			'escaped slash' => ['a\/b', 'a/b', true],
			'escaped backslash then slash' => ['a\\\\/b', 'a\\/b', true],
			'slash in a class' => ['[/]x', '/x', true],
		];
	}

	/**
	 * @dataProvider provideMatches
	 */
	public function testExpressionMatchesTheWholeValue(string $expression, string $value, bool $expected)
	{
		$this->assertSame($expected, $this->isValid($this->createValidator($expression), $value));
	}

	public function testPatternModifiersApplyAndAnchorsStayWhole()
	{
		$validator = $this->createValidator('[a-z]+');
		$validator->setPatternModifiers('im');
		$this->assertTrue($this->isValid($validator, 'HELLO'));
		$this->assertFalse($this->isValid($validator, "123\nabc"));
	}

	public function testMatchPattern()
	{
		$validator = $this->createValidator('');
		$this->assertSame('/\A(?:a|b)\z/', PradoUnit::invoke($validator, 'getMatchPattern', 'a|b'));
		$this->assertSame('/\A(?:a\/b)\z/', PradoUnit::invoke($validator, 'getMatchPattern', 'a/b'));
		$this->assertSame('/\A(?:a\/b)\z/', PradoUnit::invoke($validator, 'getMatchPattern', 'a\/b'));
		$validator->setPatternModifiers('i');
		$this->assertSame('/\A(?:x)\z/i', PradoUnit::invoke($validator, 'getMatchPattern', 'x'));
	}

	public function testEmptyValueOrExpressionIsValid()
	{
		$this->assertTrue($this->isValid($this->createValidator('\d+'), ''));
		$this->assertTrue($this->isValid($this->createValidator(''), 'anything'));
	}

	public function testEmailAddressMatchesTheWholeValue()
	{
		$validator = $this->createValidator('', TEmailAddressValidator::class);
		$this->assertTrue($this->isValid($validator, 'user@example.com'));
		$this->assertFalse($this->isValid($validator, "user@example.com\n"));
		$this->assertFalse($this->isValid($validator, 'user@example.com other'));
	}
}
