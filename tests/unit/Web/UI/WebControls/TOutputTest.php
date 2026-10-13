<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use Prado\Exceptions\TInvalidDataValueException;
use Prado\IDataRenderer;
use Prado\Web\UI\TPage;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TOutput;
use Prado\Web\UI\WebControls\TTextBox;
use Prado\Web\UI\WebControls\TWebControl;
use PHPUnit\Framework\TestCase;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;

class TOutputTest extends TestCase
{
	use TWebControlRenderTrait;

	/**
	 * Returns a page holding the output with ID Total and text boxes Price and Quantity.
	 */
	private function createPage(): array
	{
		$page = new TPage();
		$price = new TTextBox();
		$price->setID('Price');
		$quantity = new TTextBox();
		$quantity->setID('Quantity');
		$output = new TOutput();
		$output->setID('Total');
		$page->getControls()->add($price);
		$page->getControls()->add($quantity);
		$page->getControls()->add($output);
		return [$page, $output, $price, $quantity];
	}

	public function testExtendsWebControl()
	{
		$output = new TOutput();
		$this->assertInstanceOf(TWebControl::class, $output);
		$this->assertInstanceOf(IDataRenderer::class, $output);
	}

	public function testRendersOutputTagWithName()
	{
		[, $output] = $this->createPage();
		$this->assertSame('<output name="Total" id="Total"></output>', $this->render($output));
	}

	public function testTextDefaultEmpty()
	{
		$this->assertSame('', (new TOutput())->getText());
	}

	public function testTextRendered()
	{
		[, $output] = $this->createPage();
		$output->setText('42.00');
		$this->assertSame('<output name="Total" id="Total">42.00</output>', $this->render($output));
	}

	public function testTextEncoded()
	{
		[, $output] = $this->createPage();
		$output->setText('<b>1</b> &amp; "2"');
		$this->assertStringContainsString('>&lt;b&gt;1&lt;/b&gt; &amp; &quot;2&quot;</output>', $this->render($output));
	}

	public function testBodyRenderedWhenTextEmpty()
	{
		[, $output] = $this->createPage();
		$output->getControls()->add('<i>none</i>');
		$this->assertStringContainsString('><i>none</i></output>', $this->render($output));
	}

	public function testTextReplacesBody()
	{
		[, $output] = $this->createPage();
		$output->getControls()->add('none');
		$output->setText('5');
		$this->assertStringContainsString('>5</output>', $this->render($output));
	}

	public function testDataIsText()
	{
		$output = new TOutput();
		$output->setData(12.5);
		$this->assertSame('12.5', $output->getText());
		$this->assertSame('12.5', $output->getData());
	}

	public function testForControlDefaultEmpty()
	{
		$output = new TOutput();
		$this->assertSame('', $output->getForControl());
		$this->assertSame([], $output->getForControlIDs());
	}

	public function testForControlIDsSplitOnSpacesAndCommas()
	{
		$output = new TOutput();
		$output->setForControl(" Price, Quantity\tTax ");
		$this->assertSame('Price, Quantity	Tax', $output->getForControl());
		$this->assertSame(['Price', 'Quantity', 'Tax'], $output->getForControlIDs());
	}

	public function testForRendersClientIds()
	{
		[, $output] = $this->createPage();
		$output->setForControl('Price,Quantity');
		$this->assertStringContainsString(' for="Price Quantity"', $this->render($output));
	}

	public function testForLeavesOutInvisibleControl()
	{
		[, $output, , $quantity] = $this->createPage();
		$quantity->setVisible(false);
		$output->setForControl('Price Quantity');
		$this->assertStringContainsString(' for="Price"', $this->render($output));
	}

	public function testForNotRenderedWhenNoControlVisible()
	{
		[, $output, $price] = $this->createPage();
		$price->setVisible(false);
		$output->setForControl('Price');
		$this->assertStringNotContainsString(' for=', $this->render($output));
	}

	public function testForControlNotFoundThrows()
	{
		[, $output] = $this->createPage();
		$output->setForControl('Price Missing');
		$this->expectException(TInvalidDataValueException::class);
		$this->expectExceptionMessage("TOutput.ForControl 'Missing' cannot be found.");
		$this->render($output);
	}

	public function testLabelForOutput()
	{
		[$page, $output] = $this->createPage();
		$label = new TLabel();
		$label->setForControl('Total');
		$label->setText('Total');
		$page->getControls()->add($label);
		$this->assertSame('<label for="Total">Total</label>', $this->render($label));
		$this->assertStringContainsString('id="Total"', $this->render($output));
	}

	public function testNoRoleOrAriaRendered()
	{
		[, $output] = $this->createPage();
		$output->setText('1');
		$html = $this->render($output);
		$this->assertStringNotContainsString('role=', $html);
		$this->assertStringNotContainsString('aria-', $html);
	}
}
