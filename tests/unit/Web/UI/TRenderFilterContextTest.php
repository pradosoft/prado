<?php

namespace Prado\Test\Unit\Web\UI;

use PHPUnit\Framework\TestCase;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TRenderFilterContext;
use Prado\Web\UI\TRenderFilterParameter;

/**
 * Tests for {@see TRenderFilterContext} and {@see TControl::getRenderFilterExempt}.
 *
 * Each filter appends `-<tag>` to every word `html` it sees, so the output shows
 * which filters saw each word.
 */
class TRenderFilterContextTest extends TestCase
{
	/**
	 * A control rendering `[name `, its children, and ` name]`.
	 */
	private function box(string $name, mixed ...$children): TControl
	{
		$control = new TControl();
		$control->getControls()->add('[' . $name . ' ');
		foreach ($children as $child) {
			$control->getControls()->add($child);
		}
		$control->getControls()->add(' ' . $name . ']');
		return $control;
	}

	private function filter(TControl $control, string $tag): TControl
	{
		$control->onRenderFilter[] = function ($sender, TRenderFilterParameter $param) use ($tag) {
			$param->setFilterText(preg_replace('/\bhtml\b/', 'html-' . $tag, $param->getFilterText()));
		};
		return $control;
	}

	private function exempt(TControl $control, mixed $value): TControl
	{
		$control->setRenderFilterExempt($value);
		return $control;
	}

	private function render(TControl $control): string
	{
		$writer = new THtmlWriter();
		$control->renderControl($writer);
		return $writer->flush();
	}

	// ================================================================================
	// TControl::RenderFilterExempt
	// ================================================================================

	public function testRenderFilterExemptDefaultsToNull()
	{
		$this->assertNull((new TControl())->getRenderFilterExempt());
	}

	public static function exemptValueProvider(): array
	{
		return [
			'true' => [true, true],
			'false' => [false, false],
			'"true"' => ['true', true],
			'"false"' => ['false', false],
			'"FALSE"' => ['FALSE', false],
			'null' => [null, null],
			'empty' => ['', null],
			'blank' => ['  ', null],
		];
	}

	/**
	 * @dataProvider exemptValueProvider
	 */
	public function testSetRenderFilterExempt(mixed $value, ?bool $expected)
	{
		$control = new TControl();
		$control->setRenderFilterExempt(true);
		$control->setRenderFilterExempt($value);
		$this->assertSame($expected, $control->getRenderFilterExempt());
	}

	// ================================================================================
	// Exempt output
	// ================================================================================

	public function testExemptChildIsHiddenFromParentFilter()
	{
		$child = $this->exempt($this->box('E', 'html'), true);
		$this->assertSame('[F html-1[E html E] F]', $this->render($this->filter($this->box('F', 'html', $child), '1')));
	}

	public function testExemptChildOwnFilterSeesItsOutput()
	{
		$child = $this->exempt($this->filter($this->box('E', 'html'), 'e'), true);
		$this->assertSame('[F html-1[E html-e E] F]', $this->render($this->filter($this->box('F', 'html', $child), '1')));
	}

	public function testExemptWithoutFilterRendersUnchanged()
	{
		$writer = new THtmlWriter();
		$this->box('P', $this->exempt($this->box('E', 'html'), true))->renderControl($writer);
		$this->assertSame('[P [E html E] P]', $writer->flush());
		$this->assertNull($writer->getRenderFilterContext());
	}

	public function testExemptIsHiddenFromEveryEnclosingFilter()
	{
		$inner = $this->exempt($this->box('E2', 'html'), true);
		$filter = $this->filter($this->box('F3', 'html', $inner), '3');
		$outer = $this->exempt($this->box('E1', 'html', $filter), true);
		$this->assertSame(
			'[F1 html-1[E1 html[F3 html-3[E2 html E2] F3] E1] F1]',
			$this->render($this->filter($this->box('F1', 'html', $outer), '1'))
		);
	}

	public function testFalseOutsideExemptRendersUnchanged()
	{
		$child = $this->exempt($this->box('R', 'html'), false);
		$this->assertSame('[F html-1[R html-1 R] F]', $this->render($this->filter($this->box('F', 'html', $child), '1')));
	}

	// ================================================================================
	// Re-enabled output
	// ================================================================================

	public function testReEnabledInsideExemptIsFiltered()
	{
		$enabled = $this->exempt($this->box('R', 'html'), false);
		$exempt = $this->exempt($this->box('E', 'html', $enabled), true);
		$this->assertSame(
			'[F html-1[E html[R html-1 R] E] F]',
			$this->render($this->filter($this->box('F', 'html', $exempt), '1'))
		);
	}

	public function testReEnabledPassesExemptFilterThenOuterFilters()
	{
		$inner = $this->exempt($this->box('E2', 'html'), true);
		$enabled = $this->exempt($this->box('R', 'html', $inner), false);
		$exempt = $this->exempt($this->filter($this->box('E1', 'html', $enabled), 'e'), true);
		$this->assertSame(
			'[F2 [F1 html-2-1[E1 html-e[R html-2-1-e[E2 html E2] R] E1] F1] F2]',
			$this->render($this->filter($this->box('F2', $this->filter($this->box('F1', 'html', $exempt), '1')), '2'))
		);
	}

	public function testNestedReEnabledInsideNestedExempt()
	{
		$r2 = $this->exempt($this->box('R2', 'html'), false);
		$e2 = $this->exempt($this->box('E2', 'html', $r2), true);
		$r1 = $this->exempt($this->box('R1', 'html', $e2), false);
		$e1 = $this->exempt($this->box('E1', 'html', $r1), true);
		$this->assertSame(
			'[F html-1[E1 html[R1 html-1[E2 html[R2 html-1 R2] E2] R1] E1] F]',
			$this->render($this->filter($this->box('F', 'html', $e1), '1'))
		);
	}

	public function testFilterSeesPlaceholdersAndMarkersAsComments()
	{
		$seen = '';
		$enabled = $this->exempt($this->box('R', 'x'), false);
		$exempt = $this->exempt($this->box('E', 'secret', $enabled), true);
		$filter = $this->box('F', $exempt);
		$filter->onRenderFilter[] = function ($sender, TRenderFilterParameter $param) use (&$seen) {
			$seen = $param->getFilterText();
		};
		$output = $this->render($filter);
		$this->assertStringNotContainsString('secret', $seen);
		$this->assertStringContainsString('<!--' . TRenderFilterContext::EXEMPT_TOKEN, $seen);
		$this->assertStringContainsString('[R x R]', $seen);
		$this->assertSame('[F [E secret[R x R] E] F]', $output);
	}

	public function testHandlerRemovingPlaceholderRemovesExemptOutput()
	{
		$exempt = $this->exempt($this->box('E', 'html'), true);
		$filter = $this->box('F', $exempt);
		$filter->onRenderFilter[] = function ($sender, TRenderFilterParameter $param) {
			$param->setFilterText(preg_replace('/<!--.*?-->/', '', $param->getFilterText()));
		};
		$this->assertSame('[F  F]', $this->render($filter));
	}

	public function testSecondRenderRestoresIndependently()
	{
		$writer = new THtmlWriter();
		$exempt = $this->exempt($this->box('E', 'html'), true);
		$filter = $this->filter($this->box('F', $exempt), '1');
		$filter->renderControl($writer);
		$filter->renderControl($writer);
		$this->assertSame('[F [E html E] F][F [E html E] F]', $writer->flush());
		$this->assertFalse($writer->getRenderFilterContext()->getIsCapturing());
		$this->assertFalse($writer->getRenderFilterContext()->getIsExempt());
	}

	public function testExemptMarkupIsHiddenFromNestedFilters()
	{
		$inner = $this->filter($this->box('F2', '<pre data-prado-filter-exempt="">html</pre> html'), '2');
		$this->assertSame(
			'[F1 html-1[F2 <pre data-prado-filter-exempt="">html</pre> html-1-2 F2] F1]',
			$this->render($this->filter($this->box('F1', 'html', $inner), '1'))
		);
	}

	// ================================================================================
	// TRenderFilterContext API
	// ================================================================================

	public function testCaptureDepth()
	{
		$context = new TRenderFilterContext();
		$this->assertFalse($context->getIsCapturing());
		$context->beginCapture();
		$context->beginCapture();
		$this->assertSame('a', $context->endCapture('a'));
		$this->assertTrue($context->getIsCapturing());
		$this->assertSame('b', $context->endCapture('b'));
		$this->assertFalse($context->getIsCapturing());
	}

	public function testBeginExemptWithoutCaptureDoesNothing()
	{
		$context = new TRenderFilterContext();
		$writer = new THtmlWriter();
		$inner = $writer->getWriter();
		$this->assertFalse($context->beginExempt($writer));
		$this->assertSame($inner, $writer->getWriter());
		$this->assertFalse($context->getIsExempt());
	}

	public function testBeginFilterOutsideExemptDoesNothing()
	{
		$context = new TRenderFilterContext();
		$writer = new THtmlWriter();
		$this->assertFalse($context->beginFilter($writer));
		$this->assertSame('', $writer->flush());
	}

	public function testEmptyExemptOutputWritesNothing()
	{
		$context = new TRenderFilterContext();
		$writer = new THtmlWriter();
		$context->beginCapture();
		$this->assertTrue($context->beginExempt($writer));
		$context->endExempt($writer);
		$this->assertSame('', $writer->flush());
	}

	public function testHtmlWriterRenderFilterContext()
	{
		$writer = new THtmlWriter();
		$this->assertNull($writer->getRenderFilterContext());
		$context = new TRenderFilterContext();
		$this->assertSame($writer, $writer->setRenderFilterContext($context));
		$this->assertSame($context, $writer->getRenderFilterContext());
	}
}
