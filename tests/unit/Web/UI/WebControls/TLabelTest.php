<?php

use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\THtmlWriter;
use Prado\IO\TTextWriter;
use Prado\Exceptions\TInvalidDataValueException;
use PHPUnit\Framework\TestCase;

class TLabelTest extends TestCase
{
	private function render($control)
	{
		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$control->render($writer);
		return $tw->flush();
	}

	private function getTagName($label)
	{
		$method = new ReflectionMethod($label, 'getTagName');
		$method->setAccessible(true);
		return $method->invoke($label);
	}

	// ================================================================================
	// Constructor and Default State Tests
	// ================================================================================

	public function testDefaultState()
	{
		$label = new TLabel();
		$this->assertEquals('', $label->getText());
		$this->assertEquals('', $label->getForControl());
		$this->assertEquals('span', $this->getTagName($label));
	}

	public function testExtendsTWebControl()
	{
		$label = new TLabel();
		$this->assertInstanceOf(\Prado\Web\UI\WebControls\TWebControl::class, $label);
	}

	// ================================================================================
	// Text Property Tests
	// ================================================================================

	public function testSetText()
	{
		$label = new TLabel();
		$label->setText('Test');
		$this->assertEquals('Test', $label->getText());
	}

	public function testSetTextWithEmptyString()
	{
		$label = new TLabel();
		$label->setText('Initial');
		$label->setText('');
		$this->assertEquals('', $label->getText());
	}

	public function testSetTextWithWhitespace()
	{
		$label = new TLabel();
		$label->setText('  Test with spaces  ');
		$this->assertEquals('  Test with spaces  ', $label->getText());
	}

	public function testSetTextWithInteger()
	{
		$label = new TLabel();
		$label->setText(123);
		$this->assertEquals('123', $label->getText());
	}

	public function testGetDataReturnsText()
	{
		$label = new TLabel();
		$label->setText('Data Text');
		$this->assertEquals('Data Text', $label->getData());
	}

	public function testSetDataSetsText()
	{
		$label = new TLabel();
		$label->setData('Set Data');
		$this->assertEquals('Set Data', $label->getText());
	}

	// ================================================================================
	// ForControl Property Tests
	// ================================================================================

	public function testSetForControl()
	{
		$label = new TLabel();
		$label->setForControl('input1');
		$this->assertEquals('input1', $label->getForControl());
	}

	public function testForControlChangesTagName()
	{
		$label = new TLabel();
		$this->assertEquals('span', $this->getTagName($label));

		$label->setForControl('input1');
		$this->assertEquals('label', $this->getTagName($label));
	}

	public function testSetForControlWithEmptyString()
	{
		$label = new TLabel();
		$label->setForControl('input1');
		$label->setForControl('');
		$this->assertEquals('', $label->getForControl());
		$this->assertEquals('span', $this->getTagName($label));
	}

	// ================================================================================
	// Rendering Tests
	// ================================================================================

	public function testRenderSpanWithoutText()
	{
		$label = new TLabel();
		$label->setID('label1');
		$output = $this->render($label);
		$this->assertStringContainsString('<span', $output);
		$this->assertStringContainsString('id="label1"', $output);
		$this->assertStringContainsString('</span>', $output);
	}

	public function testRenderSpanWithText()
	{
		$label = new TLabel();
		$label->setID('label1');
		$label->setText('Hello World');
		$output = $this->render($label);
		$this->assertStringContainsString('<span', $output);
		$this->assertStringContainsString('Hello World', $output);
		$this->assertStringContainsString('</span>', $output);
	}

	public function testRenderSpanWithCssClass()
	{
		$label = new TLabel();
		$label->setID('label1');
		$label->setCssClass('my-label');
		$output = $this->render($label);
		$this->assertStringContainsString('class="my-label"', $output);
	}

	public function testRenderLabelWithForControlWithoutControl()
	{
		$label = new TLabel();
		$label->setID('label1');
		$label->setForControl('nonexistent');

		$this->expectException(TInvalidDataValueException::class);
		$this->render($label);
	}

	public function testRenderLabelWithForControlWithHiddenControl()
	{
		$page = new \Prado\Web\UI\TPage();
		$label = new TLabel();
		$label->setID('label1');
		$textbox = new TTextBox();
		$textbox->setID('input1');
		$textbox->setVisible(false);
		$page->getControls()->add($textbox);
		$page->getControls()->add($label);
		$label->setForControl('input1');

		$output = $this->render($label);
		$this->assertStringNotContainsString('<label', $output);
		$this->assertStringNotContainsString('</label>', $output);
	}

	public function testRenderLabelWithForControlWithVisibleControl()
	{
		$page = new \Prado\Web\UI\TPage();
		$label = new TLabel();
		$label->setID('label1');
		$label->setText('Click me');
		$textbox = new TTextBox();
		$textbox->setID('input1');
		$page->getControls()->add($textbox);
		$page->getControls()->add($label);
		$label->setForControl('input1');

		$output = $this->render($label);
		$this->assertStringContainsString('<label', $output);
		$this->assertStringContainsString('for="input1"', $output);
		$this->assertStringContainsString('Click me', $output);
		$this->assertStringContainsString('</label>', $output);
	}

	public function testRenderLabelWithForControlReplacesControlId()
	{
		$page = new \Prado\Web\UI\TPage();
		$label = new TLabel();
		$label->setID('label1');
		$label->setText('Click me');
		$textbox = new TTextBox();
		$textbox->setID('input1');
		$page->getControls()->add($textbox);
		$page->getControls()->add($label);
		$label->setForControl('input1');

		$output = $this->render($label);
		$this->assertStringContainsString('for="input1"', $output);
	}

	// ================================================================================
	// Render Contents Tests
	// ================================================================================

	public function testRenderContentsWithText()
	{
		$label = new TLabel();
		$label->setText('Custom Text');

		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$label->renderContents($writer);

		$this->assertEquals('Custom Text', $tw->flush());
	}

	public function testRenderContentsWithEmptyText()
	{
		$label = new TLabel();
		$label->setText('');

		$tw = new TTextWriter();
		$writer = new THtmlWriter($tw);
		$writer->write('Default Content');
		$label->renderContents($writer);

		$this->assertEquals('Default Content', $tw->flush());
	}

	// ================================================================================
	// Visibility Tests
	// ================================================================================

	public function testRenderWhenDisabled()
	{
		$label = new TLabel();
		$label->setID('label1');
		$label->setEnabled(false);
		$output = $this->render($label);
		$this->assertStringContainsString('<span', $output);
	}

	// ================================================================================
	// IDataRenderer Interface Tests
	// ================================================================================

	public function testDataAndTextAreLinked()
	{
		$label = new TLabel();
		$label->setText('Linked Text');
		$this->assertEquals('Linked Text', $label->getData());

		$label->setData('New Data');
		$this->assertEquals('New Data', $label->getText());
	}

	// ================================================================================
	// Style Tests
	// ================================================================================

	public function testSetStyleWithForeColor()
	{
		$label = new TLabel();
		$label->setForeColor('blue');
		$this->assertEquals('blue', $label->getForeColor());
	}

	public function testSetStyleWithBackColor()
	{
		$label = new TLabel();
		$label->setBackColor('#FF0000');
		$this->assertEquals('#FF0000', $label->getBackColor());
	}
}
