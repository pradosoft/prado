<?php

use Prado\Web\UI\WebControls\TFigureCaptionOrder;
use PHPUnit\Framework\TestCase;

class TFigureCaptionOrderTest extends TestCase
{
	public function testConstantsExist()
	{
		$this->assertEquals('None', TFigureCaptionOrder::None);
		$this->assertEquals('First', TFigureCaptionOrder::First);
		$this->assertEquals('Last', TFigureCaptionOrder::Last);
	}

	public function testAllValuesUnique()
	{
		$values = [
			TFigureCaptionOrder::None,
			TFigureCaptionOrder::First,
			TFigureCaptionOrder::Last,
		];
		$this->assertCount(count($values), array_unique($values), 'All TFigureCaptionOrder values must be unique');
	}

	public function testExtendsEnumerable()
	{
		$this->assertTrue(is_a(TFigureCaptionOrder::class, \Prado\TEnumerable::class, true));
	}

	public function testConstantTypes()
	{
		$this->assertIsString(TFigureCaptionOrder::None);
		$this->assertIsString(TFigureCaptionOrder::First);
		$this->assertIsString(TFigureCaptionOrder::Last);
	}
}
