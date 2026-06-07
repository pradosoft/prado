<?php

use Prado\Web\UI\WebControls\TEnterKeyHint;
use PHPUnit\Framework\TestCase;

class TEnterKeyHintTest extends TestCase
{
	public function testConstantsExist()
	{
		$this->assertEquals('Done', TEnterKeyHint::Done);
		$this->assertEquals('Enter', TEnterKeyHint::Enter);
		$this->assertEquals('Go', TEnterKeyHint::Go);
		$this->assertEquals('Next', TEnterKeyHint::Next);
		$this->assertEquals('Previous', TEnterKeyHint::Previous);
		$this->assertEquals('Search', TEnterKeyHint::Search);
		$this->assertEquals('Send', TEnterKeyHint::Send);
	}

	public function testAllValuesUnique()
	{
		$values = [
			TEnterKeyHint::Done,
			TEnterKeyHint::Enter,
			TEnterKeyHint::Go,
			TEnterKeyHint::Next,
			TEnterKeyHint::Previous,
			TEnterKeyHint::Search,
			TEnterKeyHint::Send,
		];
		$this->assertCount(count($values), array_unique($values), 'All TEnterKeyHint values must be unique');
	}

	public function testExtendsEnumerable()
	{
		$this->assertTrue(is_a(TEnterKeyHint::class, \Prado\TEnumerable::class, true));
	}

	public function testIsValidConstant()
	{
		// TEnumerable::isValidValue or using ensureEnum via TPropertyValue
		$this->assertNotEmpty(TEnterKeyHint::Done);
		$this->assertIsString(TEnterKeyHint::Done);
	}
}
