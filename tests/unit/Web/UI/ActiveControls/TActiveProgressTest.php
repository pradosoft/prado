<?php

use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\TActiveProgress;
use Prado\Web\UI\WebControls\TProgress;
use PHPUnit\Framework\TestCase;

class TActiveProgressTest extends TestCase
{
	public function testExtendsTProgress()
	{
		$control = new TActiveProgress();
		$this->assertInstanceOf(TProgress::class, $control);
	}

	public function testImplementsIActiveControl()
	{
		$control = new TActiveProgress();
		$this->assertInstanceOf(IActiveControl::class, $control);
	}

	public function testGetActiveControlReturnsBaseActiveControl()
	{
		$control = new TActiveProgress();
		$this->assertInstanceOf(
			\Prado\Web\UI\ActiveControls\TBaseActiveControl::class,
			$control->getActiveControl()
		);
	}

	// --- setValue / setMax without active page context (no canUpdateClientSide) ---

	public function testSetValueWithoutPage()
	{
		$control = new TActiveProgress();
		// canUpdateClientSide() returns false without a page, so no client update attempted
		$control->setValue(0.5);
		$this->assertSame(0.5, $control->getValue());
	}

	public function testSetValueNullWithoutPage()
	{
		$control = new TActiveProgress();
		$control->setValue(0.5);
		$control->setValue(null);
		$this->assertNull($control->getValue());
	}

	public function testSetValueSameValueNoOp()
	{
		$control = new TActiveProgress();
		$control->setValue(0.5);
		// Setting the same value again short-circuits before any client update
		$control->setValue('0.5');
		$this->assertSame(0.5, $control->getValue());
	}

	public function testSetValueNullTwiceNoOp()
	{
		$control = new TActiveProgress();
		// null -> null takes the no-change path before any client update
		$control->setValue(null);
		$this->assertNull($control->getValue());
	}

	public function testSetMaxSameValueNoOp()
	{
		$control = new TActiveProgress();
		$control->setMax(100);
		// Setting the same max again short-circuits before any client update
		$control->setMax('100');
		$this->assertSame(100.0, $control->getMax());
	}

	public function testSetMaxWithoutPage()
	{
		$control = new TActiveProgress();
		$control->setMax(100);
		$this->assertSame(100.0, $control->getMax());
	}

	public function testSetValueNegativeThrows()
	{
		$control = new TActiveProgress();
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$control->setValue(-1);
	}

	public function testSetMaxZeroThrows()
	{
		$control = new TActiveProgress();
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$control->setMax(0);
	}

	// Note: rendering TActiveProgress requires a full page context; client-side
	// attribute updates are covered by the ActiveProgressTestCase functional test.
}
