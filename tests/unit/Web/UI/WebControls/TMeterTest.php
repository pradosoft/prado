<?php

use Prado\Web\UI\WebControls\TMeter;
use PHPUnit\Framework\TestCase;

class TMeterTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testRendersMeterTag()
	{
		$control = new TMeter();
		$output = $this->render($control);
		$this->assertStringContainsString('<meter', $output);
		$this->assertStringContainsString('</meter>', $output);
	}

	public function testExtendsWebControl()
	{
		$control = new TMeter();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, $control);
	}

	// --- Defaults ---

	public function testDefaults()
	{
		$control = new TMeter();
		$this->assertSame(0.0, $control->getValue());
		$this->assertSame(0.0, $control->getMin());
		$this->assertSame(1.0, $control->getMax());
		$this->assertNull($control->getLow());
		$this->assertNull($control->getHigh());
		$this->assertNull($control->getOptimum());
	}

	// --- Setters ---

	public function testSetValue()
	{
		$control = new TMeter();
		$control->setValue(0.6);
		$this->assertSame(0.6, $control->getValue());
		$control->setValue('70');
		$this->assertSame(70.0, $control->getValue());
	}

	public function testSetMinNegativeAllowed()
	{
		$control = new TMeter();
		$control->setMin(-10);
		$this->assertSame(-10.0, $control->getMin());
	}

	public function testSetMax()
	{
		$control = new TMeter();
		$control->setMax(100);
		$this->assertSame(100.0, $control->getMax());
	}

	public function testSetLowHighOptimum()
	{
		$control = new TMeter();
		$control->setLow(25);
		$control->setHigh(85);
		$control->setOptimum(10);
		$this->assertSame(25.0, $control->getLow());
		$this->assertSame(85.0, $control->getHigh());
		$this->assertSame(10.0, $control->getOptimum());
	}

	public function testSetLowHighOptimumNullClears()
	{
		$control = new TMeter();
		$control->setLow(25);
		$control->setLow(null);
		$this->assertNull($control->getLow());

		$control->setHigh(85);
		$control->setHigh('');
		$this->assertNull($control->getHigh());

		$control->setOptimum(10);
		$control->setOptimum(null);
		$this->assertNull($control->getOptimum());
	}

	// --- Rendering ---

	public function testValueAttributeAlwaysRendered()
	{
		$control = new TMeter();
		$output = $this->render($control);
		$this->assertStringContainsString('value="0"', $output);
	}

	public function testMinMaxAttributesNotRenderedAtDefaults()
	{
		$control = new TMeter();
		$output = $this->render($control);
		$this->assertStringNotContainsString('min=', $output);
		$this->assertStringNotContainsString('max=', $output);
	}

	public function testAllAttributesRendered()
	{
		$control = new TMeter();
		$control->setValue(70);
		$control->setMin(-10);
		$control->setMax(100);
		$control->setLow(25);
		$control->setHigh(85);
		$control->setOptimum(10);
		$output = $this->render($control);
		$this->assertStringContainsString('value="70"', $output);
		$this->assertStringContainsString('min="-10"', $output);
		$this->assertStringContainsString('max="100"', $output);
		$this->assertStringContainsString('low="25"', $output);
		$this->assertStringContainsString('high="85"', $output);
		$this->assertStringContainsString('optimum="10"', $output);
	}

	public function testLowHighOptimumNotRenderedWhenNull()
	{
		$control = new TMeter();
		$output = $this->render($control);
		$this->assertStringNotContainsString('low=', $output);
		$this->assertStringNotContainsString('high=', $output);
		$this->assertStringNotContainsString('optimum=', $output);
	}

	public function testScaleAttributesRenderedBeforeValue()
	{
		// Cosmetic serialization order: min, max, low, high, optimum, then value
		$control = new TMeter();
		$control->setValue(70);
		$control->setMin(-10);
		$control->setMax(100);
		$control->setLow(25);
		$output = $this->render($control);
		$valuePos = strpos($output, 'value=');
		$this->assertLessThan($valuePos, strpos($output, 'min='));
		$this->assertLessThan($valuePos, strpos($output, 'max='));
		$this->assertLessThan($valuePos, strpos($output, 'low='));
	}

	public function testNegativeValueRendered()
	{
		$control = new TMeter();
		$control->setMin(-10);
		$control->setValue(-5);
		$output = $this->render($control);
		$this->assertStringContainsString('value="-5"', $output);
	}

	public function testDecimalPrecisionRendered()
	{
		$control = new TMeter();
		$control->setValue('0.33');
		$output = $this->render($control);
		$this->assertStringContainsString('value="0.33"', $output);
	}

	public function testMinMaxResetToDefaultsOmitAttributes()
	{
		$control = new TMeter();
		$control->setMin(-10);
		$control->setMax(100);
		$control->setMin(0.0);
		$control->setMax(1.0);
		$output = $this->render($control);
		$this->assertStringNotContainsString('min=', $output);
		$this->assertStringNotContainsString('max=', $output);
	}

	public function testNotVisibleRendersNothing()
	{
		// Visibility is enforced by renderControl(), the framework's render entry point
		$control = new TMeter();
		$control->setValue(0.5);
		$control->setVisible(false);
		$textWriter = new \Prado\IO\TTextWriter();
		$control->renderControl(new \Prado\Web\UI\THtmlWriter($textWriter));
		$this->assertSame('', $textWriter->flush());
	}

	public function testCssClassAndAttributesRendered()
	{
		$control = new TMeter();
		$control->setCssClass('disk-gauge');
		$control->getAttributes()->add('data-drive', 'sda1');
		$output = $this->render($control);
		$this->assertStringContainsString('class="disk-gauge"', $output);
		$this->assertStringContainsString('data-drive="sda1"', $output);
	}

	public function testFallbackChildContentRendered()
	{
		$control = new TMeter();
		$control->setValue(0.6);
		$control->getControls()->add('60%');
		$output = $this->render($control);
		$this->assertStringContainsString('>60%</meter>', $output);
	}
}
