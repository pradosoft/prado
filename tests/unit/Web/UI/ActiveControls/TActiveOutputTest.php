<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\TActiveOutput;
use Prado\Web\UI\ActiveControls\TBaseActiveControl;
use Prado\Web\UI\TControl;
use Prado\Web\UI\WebControls\TOutput;
use Prado\Web\UI\WebControls\TTextBox;
use PHPUnit\Framework\TestCase;

class TActiveOutputTest extends TestCase
{
	use TWebControlRenderTrait;

	/**
	 * Returns a loaded callback page holding the output Total and the text boxes Price and Quantity.
	 */
	private function createLoadedOutput(bool $callback = true): array
	{
		$page = new TTestCallbackPage();
		$page->callback = $callback;
		foreach (['Price', 'Quantity'] as $id) {
			$textbox = new TTextBox();
			$textbox->setID($id);
			$page->getControls()->add($textbox);
		}
		$output = new TActiveOutput();
		$output->setID('Total');
		$page->getControls()->add($output);
		PradoUnit::invoke($output, 'setControlStage', TControl::CS_LOADED);
		return [$page, $output];
	}

	public function testExtendsTOutput()
	{
		$output = new TActiveOutput();
		$this->assertInstanceOf(TOutput::class, $output);
		$this->assertInstanceOf(IActiveControl::class, $output);
		$this->assertInstanceOf(TBaseActiveControl::class, $output->getActiveControl());
	}

	public function testEnsureIdAlwaysTrue()
	{
		$this->assertTrue((new TActiveOutput())->getEnsureId());
	}

	public function testIdRenderedWithoutExplicitId()
	{
		$page = new TTestCallbackPage();
		$output = new TActiveOutput();
		$page->getControls()->add($output);
		$this->assertStringContainsString(' id="' . $output->getClientID() . '"', $this->render($output));
	}

	public function testSettersWithoutPage()
	{
		$output = new TActiveOutput();
		$output->setText('5');
		$output->setForControl('Price');
		$this->assertSame('5', $output->getText());
		$this->assertSame('Price', $output->getForControl());
	}

	public function testSetTextUpdatesClient()
	{
		[$page, $output] = $this->createLoadedOutput();
		$output->setText('42.00');
		$this->assertSame(
			[['Prado.Element.j' => ['Total', 'html', ['42.00']]]],
			$page->getClientFunctions()
		);
	}

	public function testSetTextSendsEncodedText()
	{
		[$page, $output] = $this->createLoadedOutput();
		$output->setText('<img src=x> &amp;');
		$this->assertSame(
			[['Prado.Element.j' => ['Total', 'html', ['&lt;img src=x&gt; &amp;']]]],
			$page->getClientFunctions()
		);
	}

	public function testSameTextSendsNothing()
	{
		[$page, $output] = $this->createLoadedOutput();
		$output->setText('5');
		$output->setText(5);
		$this->assertCount(1, $page->getClientFunctions());
	}

	public function testSetTextNoCallbackSendsNothing()
	{
		[$page, $output] = $this->createLoadedOutput(false);
		$output->setText('5');
		$this->assertSame('5', $output->getText());
		$this->assertSame([], $page->getClientFunctions());
	}

	public function testSetForControlSetsClientAttribute()
	{
		[$page, $output] = $this->createLoadedOutput();
		$output->setForControl('Price Quantity');
		$this->assertSame(
			[['Prado.Element.setAttribute' => ['Total', 'for', 'Price Quantity']]],
			$page->getClientFunctions()
		);
	}

	public function testClearForControlRemovesClientAttribute()
	{
		[$page, $output] = $this->createLoadedOutput();
		$output->setForControl('Price');
		$output->setForControl('');
		$this->assertSame(
			['Prado.Element.removeAttribute' => ['Total', 'for']],
			$page->getClientFunctions()[1]
		);
	}

	public function testSameForControlSendsNothing()
	{
		[$page, $output] = $this->createLoadedOutput();
		$output->setForControl('Price');
		$output->setForControl(' Price ');
		$this->assertCount(1, $page->getClientFunctions());
	}

	public function testSetForControlNoCallbackSendsNothing()
	{
		[$page, $output] = $this->createLoadedOutput(false);
		$output->setForControl('Price');
		$this->assertSame([], $page->getClientFunctions());
	}
}
