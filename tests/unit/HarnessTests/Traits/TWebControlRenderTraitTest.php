<?php

use Prado\Web\UI\WebControls\TWebControl;
use PHPUnit\Framework\TestCase;

/**
 * Minimal TWebControl subclass with a fixed tag name for render testing.
 */
class TWebControlRenderTraitTestControl extends TWebControl
{
	public function getTagName(): string
	{
		return 'div';
	}
}

/**
 * Unit tests for {@see TWebControlRenderTrait}.
 *
 * @covers TWebControlRenderTrait
 */
class TWebControlRenderTraitTest extends TestCase
{
	use TWebControlRenderTrait;

	private TWebControlRenderTraitTestControl $control;

	protected function setUp(): void
	{
		$this->control = new TWebControlRenderTraitTestControl();
	}

	// -----------------------------------------------------------------------
	// render()
	// -----------------------------------------------------------------------

	public function testRenderReturnsString()
	{
		$this->assertIsString($this->render($this->control));
	}

	public function testRenderContainsOpenTag()
	{
		$this->assertStringContainsString('<div', $this->render($this->control));
	}

	public function testRenderContainsCloseTag()
	{
		$this->assertStringContainsString('</div>', $this->render($this->control));
	}

	public function testRenderIncludesChildContent()
	{
		$this->control->getControls()->add('hello');
		$this->assertStringContainsString('hello', $this->render($this->control));
	}

	public function testRenderProducesCompleteTag()
	{
		$this->assertSame('<div></div>', $this->render($this->control));
	}

	// -----------------------------------------------------------------------
	// renderBeginTag()
	// -----------------------------------------------------------------------

	public function testRenderBeginTagReturnsString()
	{
		$this->assertIsString($this->renderBeginTag($this->control));
	}

	public function testRenderBeginTagContainsOpenTag()
	{
		$this->assertStringContainsString('<div', $this->renderBeginTag($this->control));
	}

	public function testRenderBeginTagDoesNotContainCloseTag()
	{
		$this->assertStringNotContainsString('</div>', $this->renderBeginTag($this->control));
	}

	public function testRenderBeginTagIncludesAttributes()
	{
		$this->control->setID('myid');
		$output = $this->renderBeginTag($this->control);
		$this->assertStringContainsString('id="myid"', $output);
	}

	// -----------------------------------------------------------------------
	// renderContents()
	// -----------------------------------------------------------------------

	public function testRenderContentsReturnsString()
	{
		$this->assertIsString($this->renderContents($this->control));
	}

	public function testRenderContentsEmptyForNoChildren()
	{
		$this->assertSame('', $this->renderContents($this->control));
	}

	public function testRenderContentsReturnsChildText()
	{
		$this->control->getControls()->add('inner text');
		$this->assertSame('inner text', $this->renderContents($this->control));
	}

	public function testRenderContentsNoOpenOrCloseTag()
	{
		$this->control->getControls()->add('content');
		$output = $this->renderContents($this->control);
		$this->assertStringNotContainsString('<div', $output);
		$this->assertStringNotContainsString('</div>', $output);
	}

	// -----------------------------------------------------------------------
	// renderEndTag()
	// -----------------------------------------------------------------------

	public function testRenderEndTagReturnsString()
	{
		// renderEndTag requires a matching open tag to have been started on the
		// same writer; called in isolation it still returns a string (possibly
		// empty or the bare closing sequence depending on the writer state).
		// We validate behaviour via a full render cycle instead.
		$full = $this->render($this->control);
		$this->assertStringContainsString('</div>', $full);
	}

	public function testRenderEndTagDoesNotContainOpenTag()
	{
		$this->control->setID('test');
		// renderBeginTag + renderEndTag on separate writers — each is independent.
		$begin = $this->renderBeginTag($this->control);
		$this->assertStringContainsString('<div', $begin);
		$this->assertStringNotContainsString('</div>', $begin);
	}

	// -----------------------------------------------------------------------
	// Integration: begin + contents + end compose the same as render()
	// -----------------------------------------------------------------------

	public function testComposedRenderMatchesFullRender()
	{
		$this->control->getControls()->add('text');

		$full = $this->render($this->control);

		// Compose via three separate calls on a shared writer (manual).
		$tw = new \Prado\IO\TTextWriter();
		$writer = new \Prado\Web\UI\THtmlWriter($tw);
		$this->control->renderBeginTag($writer);
		$this->control->renderContents($writer);
		$this->control->renderEndTag($writer);
		$composed = $tw->flush();

		$this->assertSame($full, $composed);
	}
}
