<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\WebControls\TLabel;
use Prado\Web\UI\WebControls\TWebControlAdapter;

/**
 * Adapter that wraps each render step of its control in a marker.
 */
class TWebControlAdapterTestMarkingAdapter extends TWebControlAdapter
{
	public function renderBeginTag($writer)
	{
		$writer->write('[begin]');
		parent::renderBeginTag($writer);
	}

	public function renderContents($writer)
	{
		$writer->write('[contents]');
		parent::renderContents($writer);
	}

	public function renderEndTag($writer)
	{
		$writer->write('[end]');
		parent::renderEndTag($writer);
	}
}

class TWebControlAdapterTest extends TestCase
{
	/**
	 * Creates a label with an ID and text.
	 * @return TLabel the label
	 */
	private function createLabel(): TLabel
	{
		$label = new TLabel();
		$label->setID('lbl');
		$label->setText('Hello');
		return $label;
	}

	/**
	 * Renders through a callback that receives the writer.
	 * @param callable $render the render callback
	 * @return string the rendered markup
	 */
	private function capture(callable $render): string
	{
		$tw = new TTextWriter();
		$render(new THtmlWriter($tw));
		return $tw->flush();
	}

	public function testGetControlIsTheAttachedControl()
	{
		$label = $this->createLabel();
		$adapter = new TWebControlAdapter($label);
		$this->assertSame($label, $adapter->getControl());
	}

	public function testRenderMatchesTheControlRender()
	{
		$expected = $this->capture(fn ($writer) => $this->createLabel()->render($writer));
		$adapter = new TWebControlAdapter($this->createLabel());
		$this->assertSame('<span id="lbl">Hello</span>', $expected);
		$this->assertSame($expected, $this->capture(fn ($writer) => $adapter->render($writer)));
	}

	public function testRenderStepsDelegateToTheControl()
	{
		$adapter = new TWebControlAdapter($this->createLabel());
		$this->assertSame('<span id="lbl">', $this->capture(fn ($writer) => $adapter->renderBeginTag($writer)));
		$this->assertSame('Hello', $this->capture(fn ($writer) => $adapter->renderContents($writer)));
		$this->assertSame('<span id="lbl"></span>', $this->capture(function ($writer) use ($adapter) {
			$adapter->renderBeginTag($writer);
			$adapter->renderEndTag($writer);
		}));
	}

	public function testRenderCallsTheStepsInOrder()
	{
		$adapter = new TWebControlAdapterTestMarkingAdapter($this->createLabel());
		$this->assertSame(
			'[begin]<span id="lbl">[contents]Hello[end]</span>',
			$this->capture(fn ($writer) => $adapter->render($writer))
		);
	}

	public function testAttachedAdapterRendersTheControl()
	{
		$label = $this->createLabel();
		$label->setAdapter(new TWebControlAdapterTestMarkingAdapter($label));
		$this->assertSame(
			'[begin]<span id="lbl">[contents]Hello[end]</span>',
			$this->capture(fn ($writer) => $label->renderControl($writer))
		);
	}
}
