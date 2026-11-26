<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\TActiveMeter;
use Prado\Web\UI\WebControls\TMeter;
use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Web\UI\THtmlWriter;

class TActiveMeterTest extends TestCase
{
	public function testExtendsTMeter()
	{
		$control = new TActiveMeter();
		$this->assertInstanceOf(TMeter::class, $control);
	}

	public function testImplementsIActiveControl()
	{
		$control = new TActiveMeter();
		$this->assertInstanceOf(IActiveControl::class, $control);
	}

	public function testGetActiveControlReturnsBaseActiveControl()
	{
		$control = new TActiveMeter();
		$this->assertInstanceOf(
			\Prado\Web\UI\ActiveControls\TBaseActiveControl::class,
			$control->getActiveControl()
		);
	}

	// --- setters without active page context (no canUpdateClientSide) ---

	public function testSettersWithoutPage()
	{
		$control = new TActiveMeter();
		$control->setValue(70);
		$control->setMin(-10);
		$control->setMax(100);
		$control->setLow(25);
		$control->setHigh(85);
		$control->setOptimum(10);

		$this->assertSame(70.0, $control->getValue());
		$this->assertSame(-10.0, $control->getMin());
		$this->assertSame(100.0, $control->getMax());
		$this->assertSame(25.0, $control->getLow());
		$this->assertSame(85.0, $control->getHigh());
		$this->assertSame(10.0, $control->getOptimum());
	}

	public function testSetLowNullWithoutPage()
	{
		$control = new TActiveMeter();
		$control->setLow(25);
		$control->setLow(null);
		$this->assertNull($control->getLow());
	}

	public function testSetValueSameValueNoOp()
	{
		$control = new TActiveMeter();
		$control->setValue(0.6);
		// Setting the same value again short-circuits before any client update
		$control->setValue('0.6');
		$this->assertSame(0.6, $control->getValue());
	}

	public function testSetLowNullTwiceNoOp()
	{
		$control = new TActiveMeter();
		// null -> null takes the no-change path before any client update
		$control->setLow(null);
		$this->assertNull($control->getLow());
	}

	public function testSetMinSameValueNoOp()
	{
		$control = new TActiveMeter();
		$control->setMin(-10);
		$control->setMin('-10');
		$this->assertSame(-10.0, $control->getMin());
	}

	public function testChangesUpdateTheClientAttributesInACallback()
	{
		$page = new TActiveTestPage();
		$meter = $page->addControl(new TActiveMeter(), 'Gauge');
		$meter->setValue('0.5');
		$meter->setValue(0.5);
		$meter->setMin(-1);
		$meter->setMax(2);
		$meter->setLow(0.2);
		$meter->setHigh(0.8);
		$meter->setOptimum(0.6);
		$meter->setLow('');
		$this->assertSame([
			['Prado.Element.setAttribute' => ['Gauge', 'value', '0.5']],
			['Prado.Element.setAttribute' => ['Gauge', 'min', '-1']],
			['Prado.Element.setAttribute' => ['Gauge', 'max', '2']],
			['Prado.Element.setAttribute' => ['Gauge', 'low', '0.2']],
			['Prado.Element.setAttribute' => ['Gauge', 'high', '0.8']],
			['Prado.Element.setAttribute' => ['Gauge', 'optimum', '0.6']],
			['Prado.Element.removeAttribute' => ['Gauge', 'low']],
		], $page->getClientFunctions());
	}

	public function testChangesOutsideACallbackUpdateNothing()
	{
		$page = new TActiveTestPage(false);
		$meter = $page->addControl(new TActiveMeter(), 'Gauge');
		$meter->setValue(0.5);
		$this->assertSame([], $page->getClientFunctions());
		$this->assertSame(0.5, $meter->getValue());
	}

	public function testRenderAddsTheId()
	{
		$page = new TActiveTestPage();
		$meter = $page->addControl(new TActiveMeter(), 'Gauge');
		$writer = new THtmlWriter(new TTextWriter());
		$meter->render($writer);
		$this->assertStringStartsWith('<meter id="Gauge"', $writer->flush());
	}
}
