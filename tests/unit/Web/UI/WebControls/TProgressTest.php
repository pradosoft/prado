<?php

use Prado\Web\UI\WebControls\TProgress;
use PHPUnit\Framework\TestCase;

class TProgressTest extends TestCase
{
	use TWebControlRenderTrait;

	public function testRendersProgressTag()
	{
		$control = new TProgress();
		$output = $this->render($control);
		$this->assertStringContainsString('<progress', $output);
		$this->assertStringContainsString('</progress>', $output);
	}

	public function testExtendsWebControl()
	{
		$control = new TProgress();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, $control);
	}

	// --- Value ---

	public function testValueDefaultNull()
	{
		$control = new TProgress();
		$this->assertNull($control->getValue());
	}

	public function testSetValue()
	{
		$control = new TProgress();
		$control->setValue(0.7);
		$this->assertSame(0.7, $control->getValue());
		$control->setValue('35');
		$this->assertSame(35.0, $control->getValue());
	}

	public function testSetValueZeroAllowed()
	{
		$control = new TProgress();
		$control->setValue(0);
		$this->assertSame(0.0, $control->getValue());
	}

	public function testSetValueNullResetsToIndeterminate()
	{
		$control = new TProgress();
		$control->setValue(0.5);
		$control->setValue(null);
		$this->assertNull($control->getValue());
	}

	public function testSetValueEmptyStringResetsToIndeterminate()
	{
		$control = new TProgress();
		$control->setValue(0.5);
		$control->setValue('');
		$this->assertNull($control->getValue());
	}

	public function testSetValueNegativeThrows()
	{
		$control = new TProgress();
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$control->setValue(-0.1);
	}

	// --- Max ---

	public function testMaxDefaultOne()
	{
		$control = new TProgress();
		$this->assertSame(1.0, $control->getMax());
	}

	public function testSetMax()
	{
		$control = new TProgress();
		$control->setMax(100);
		$this->assertSame(100.0, $control->getMax());
	}

	public function testSetMaxZeroThrows()
	{
		$control = new TProgress();
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$control->setMax(0);
	}

	public function testSetMaxNegativeThrows()
	{
		$control = new TProgress();
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		$control->setMax(-1);
	}

	// --- Rendering ---

	public function testValueAttributeRendered()
	{
		$control = new TProgress();
		$control->setValue(0.7);
		$output = $this->render($control);
		$this->assertStringContainsString('value="0.7"', $output);
	}

	public function testValueAttributeNotRenderedWhenIndeterminate()
	{
		$control = new TProgress();
		$output = $this->render($control);
		$this->assertStringNotContainsString('value=', $output);
	}

	public function testValueZeroAttributeRendered()
	{
		$control = new TProgress();
		$control->setValue(0);
		$output = $this->render($control);
		$this->assertStringContainsString('value="0"', $output);
	}

	public function testMaxAttributeRenderedWhenNotDefault()
	{
		$control = new TProgress();
		$control->setValue(35);
		$control->setMax(100);
		$output = $this->render($control);
		$this->assertStringContainsString('max="100"', $output);
	}

	public function testMaxAttributeNotRenderedWhenDefault()
	{
		$control = new TProgress();
		$output = $this->render($control);
		$this->assertStringNotContainsString('max=', $output);
	}

	public function testValueGreaterThanMaxRenderedAsIs()
	{
		// The HTML specification has the browser clamp display; both attributes render unmodified
		$control = new TProgress();
		$control->setValue(5);
		$output = $this->render($control);
		$this->assertStringContainsString('value="5"', $output);
		$this->assertStringNotContainsString('max=', $output);
	}

	public function testValueDecimalPrecisionRendered()
	{
		$control = new TProgress();
		$control->setValue('0.33');
		$output = $this->render($control);
		$this->assertStringContainsString('value="0.33"', $output);
	}

	public function testMaxResetToDefaultOmitsAttribute()
	{
		$control = new TProgress();
		$control->setMax(100);
		$control->setMax(1.0);
		$output = $this->render($control);
		$this->assertStringNotContainsString('max=', $output);
	}

	public function testNotVisibleRendersNothing()
	{
		// Visibility is enforced by renderControl(), the framework's render entry point
		$control = new TProgress();
		$control->setValue(0.5);
		$control->setVisible(false);
		$textWriter = new \Prado\IO\TTextWriter();
		$control->renderControl(new \Prado\Web\UI\THtmlWriter($textWriter));
		$this->assertSame('', $textWriter->flush());
	}

	public function testCssClassAndAttributesRendered()
	{
		$control = new TProgress();
		$control->setCssClass('upload-bar');
		$control->getAttributes()->add('data-task', 'upload');
		$output = $this->render($control);
		$this->assertStringContainsString('class="upload-bar"', $output);
		$this->assertStringContainsString('data-task="upload"', $output);
	}

	public function testFallbackChildContentRendered()
	{
		$control = new TProgress();
		$control->setValue(35);
		$control->setMax(100);
		$control->getControls()->add('35%');
		$output = $this->render($control);
		$this->assertStringContainsString('>35%</progress>', $output);
	}
}
