<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\TActiveProgress;
use Prado\Web\UI\WebControls\TProgress;
use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Web\UI\THtmlWriter;

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

	public function testChangesUpdateTheClientAttributesInACallback()
	{
		$page = new TActiveTestPage();
		$progress = $page->addControl(new TActiveProgress(), 'Bar');
		$progress->setValue('5');
		$progress->setValue(5);
		$progress->setMax(10);
		$progress->setMax('10');
		$progress->setValue(null);
		$this->assertSame([
			['Prado.Element.setAttribute' => ['Bar', 'value', '5']],
			['Prado.Element.setAttribute' => ['Bar', 'max', '10']],
			['Prado.Element.removeAttribute' => ['Bar', 'value']],
		], $page->getClientFunctions());
	}

	public function testChangesOutsideACallbackUpdateNothing()
	{
		$page = new TActiveTestPage(false);
		$progress = $page->addControl(new TActiveProgress(), 'Bar');
		$progress->setValue(5);
		$progress->setMax(10);
		$this->assertSame([], $page->getClientFunctions());
		$this->assertSame(10.0, $progress->getMax());
	}

	public function testRenderAddsTheId()
	{
		$page = new TActiveTestPage();
		$progress = $page->addControl(new TActiveProgress(), 'Bar');
		$writer = new THtmlWriter(new TTextWriter());
		$progress->render($writer);
		$this->assertStringStartsWith('<progress id="Bar"', $writer->flush());
	}
}
