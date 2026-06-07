<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;
use Prado\Web\UI\WebControls\TWebInputMode;
use PHPUnit\Framework\TestCase;

class TWebInputModeTest extends TestCase
{
	public function testConstantsExist()
	{
		$this->assertEquals('None', TWebInputMode::None);
		$this->assertEquals('Decimal', TWebInputMode::Decimal);
		$this->assertEquals('Numeric', TWebInputMode::Numeric);
		$this->assertEquals('Tel', TWebInputMode::Tel);
		$this->assertEquals('Url', TWebInputMode::Url);
		$this->assertEquals('Email', TWebInputMode::Email);
		$this->assertEquals('Text', TWebInputMode::Text);
		$this->assertEquals('Search', TWebInputMode::Search);
	}

	public function testAllValuesUnique()
	{
		$values = [
			TWebInputMode::None,
			TWebInputMode::Decimal,
			TWebInputMode::Numeric,
			TWebInputMode::Tel,
			TWebInputMode::Url,
			TWebInputMode::Email,
			TWebInputMode::Text,
			TWebInputMode::Search,
		];
		$this->assertCount(count($values), array_unique($values), 'All TWebInputMode values must be unique');
	}

	public function testExtendsEnumerable()
	{
		$this->assertTrue(is_a(TWebInputMode::class, \Prado\TEnumerable::class, true));
	}

	public function testConstantTypes()
	{
		foreach ([TWebInputMode::None, TWebInputMode::Decimal, TWebInputMode::Numeric,
			TWebInputMode::Tel, TWebInputMode::Url, TWebInputMode::Email,
			TWebInputMode::Text, TWebInputMode::Search] as $val) {
			$this->assertIsString($val);
		}
	}

	public function testEnsureEnumPassesEachValidConstant()
	{
		$valid = [
			TWebInputMode::None,
			TWebInputMode::Decimal,
			TWebInputMode::Numeric,
			TWebInputMode::Tel,
			TWebInputMode::Url,
			TWebInputMode::Email,
			TWebInputMode::Text,
			TWebInputMode::Search,
		];
		foreach ($valid as $value) {
			$result = TPropertyValue::ensureEnum($value, TWebInputMode::class);
			$this->assertSame($value, $result);
		}
	}

	public function testEnsureEnumIsCaseInsensitive()
	{
		$this->assertSame(TWebInputMode::None, TPropertyValue::ensureEnum('none', TWebInputMode::class));
		$this->assertSame(TWebInputMode::Numeric, TPropertyValue::ensureEnum('NUMERIC', TWebInputMode::class));
		$this->assertSame(TWebInputMode::Email, TPropertyValue::ensureEnum('EMAIL', TWebInputMode::class));
	}

	public function testEnsureEnumThrowsOnInvalidValue()
	{
		$this->expectException(TInvalidDataValueException::class);
		TPropertyValue::ensureEnum('invalid', TWebInputMode::class);
	}

	public function testEnsureEnumThrowsOnEmptyString()
	{
		$this->expectException(TInvalidDataValueException::class);
		TPropertyValue::ensureEnum('', TWebInputMode::class);
	}
}
