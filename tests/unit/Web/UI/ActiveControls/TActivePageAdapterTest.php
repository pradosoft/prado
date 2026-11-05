<?php

namespace Prado\Test\Unit\Web\UI\ActiveControls;

use PHPUnit\Framework\TestCase;
use Prado\IO\TTextWriter;
use Prado\Test\Unit\Harness\Web\UI\TTestCallbackPage;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\ActiveControls\TActivePageAdapter;
use Prado\Web\UI\ActiveControls\TActivePanel;
use Prado\Web\UI\ActiveControls\TCallbackResponseWriter;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TRenderFilterParameter;
use Prado\Web\UI\WebControls\TAbbr;
use Prado\Web\UI\WebControls\THtmlElement;

/**
 * Tests for the deferred rendering of {@see TActivePageAdapter} and
 * {@see TCallbackResponseWriter::flushWithoutBoundary}.
 *
 * The filter appends `-f` to every word `html` it sees.
 */
class TActivePageAdapterTest extends TestCase
{
	/**
	 * Returns a prerendered active panel with ID Live on a callback page, holding the strings and controls.
	 */
	private function createPanel(mixed ...$children): array
	{
		$page = new TTestCallbackPage();
		$panel = new TActivePanel();
		$panel->setID('Live');
		$page->getControls()->add($panel);
		foreach ($children as $child) {
			$panel->getControls()->add($child);
		}
		PradoUnit::invoke($panel, 'setControlStage', TControl::CS_PRERENDERED);
		return [$page, $panel];
	}

	private function filter(TControl $control, string $tag = 'f', ?array &$fragments = null): TControl
	{
		$control->onRenderFilter[] = function ($sender, TRenderFilterParameter $param) use ($tag, &$fragments) {
			$fragments[] = $param->getIsFragment();
			$param->setFilterText(preg_replace('/\bhtml\b/', 'html-' . $tag, $param->getFilterText()));
		};
		return $control;
	}

	/**
	 * Wraps the panel in an outer and a top control, each filtering with its name.
	 * A later filter adds its name before those of the earlier ones.
	 */
	private function nest(TActivePanel $panel, bool $outerFragments = true, bool $topFragments = true, ?array &$fragments = null): array
	{
		$outer = $this->filter(new TControl(), 'outer', $fragments);
		$outer->setRenderFilterFragments($outerFragments);
		$top = $this->filter(new TControl(), 'top');
		$top->setRenderFilterFragments($topFragments);
		$parent = $panel->getParent();
		$parent->getControls()->remove($panel);
		$outer->getControls()->add($panel);
		$top->getControls()->add($outer);
		$parent->getControls()->add($top);
		return [$outer, $top];
	}

	private function adapter(): TActivePageAdapter
	{
		return PradoUnit::reflectionClass(TActivePageAdapter::class)->newInstanceWithoutConstructor();
	}

	private function renderDeferred(TControl $control, THtmlWriter $writer): void
	{
		PradoUnit::invoke($this->adapter(), 'renderDeferredControl', $control, $writer);
	}

	/**
	 * Places the panel in an ancestor that filters fragments, between the strings,
	 * and returns the ancestor and the HTML its handler sees.
	 */
	private function enclose(TActivePanel $panel, string $before, string $after, ?TControl $ancestor = null): array
	{
		$ancestor ??= new TControl();
		$ancestor->setRenderFilterFragments(true);
		$seen = new \ArrayObject();
		$ancestor->onRenderFilter[] = function ($sender, TRenderFilterParameter $param) use ($seen) {
			$seen[] = $param->getFilterText();
			$param->setFilterText(preg_replace('/\bhtml\b/', 'html-a', $param->getFilterText()));
		};
		$parent = $panel->getParent();
		$parent->getControls()->remove($panel);
		$ancestor->getControls()->add($before);
		$ancestor->getControls()->add($panel);
		$ancestor->getControls()->add($after);
		$parent->getControls()->add($ancestor);
		return [$ancestor, $seen];
	}

	private function bounded(TCallbackResponseWriter $inner, string $content): string
	{
		return '<!--' . $inner->getBoundary() . '-->' . $content . '<!--//' . $inner->getBoundary() . '-->';
	}

	public function testFlushWithoutBoundary()
	{
		$writer = new TCallbackResponseWriter();
		$writer->write('a');
		$this->assertSame('a', $writer->flushWithoutBoundary());
		$this->assertSame('', $writer->flushWithoutBoundary());
		$this->assertSame('', $writer->flush());
	}

	public function testUnfilteredControlRendersUnchanged()
	{
		[$page, $panel] = $this->createPanel('html');
		$inner = new TCallbackResponseWriter();
		$this->renderDeferred($panel, new THtmlWriter($inner));
		$this->assertSame($this->bounded($inner, '<div id="Live">html</div>'), $inner->flush());
	}

	public function testFilteredControlKeepsBoundary()
	{
		[$page, $panel] = $this->createPanel('html');
		$inner = new TCallbackResponseWriter();
		$writer = new THtmlWriter($inner);
		$this->renderDeferred($this->filter($panel), $writer);
		$this->assertSame($inner, $writer->getWriter());
		$this->assertSame($this->bounded($inner, '<div id="Live">html-f</div>'), $inner->flush());
		$this->assertSame(
			[['Prado.Element.replace' => ['Live', null, $inner->getBoundary(), true]]],
			$page->getClientFunctions()
		);
	}

	public function testExemptDescendantIsHiddenFromFilter()
	{
		$exempt = new TControl();
		$exempt->getControls()->add('html');
		$exempt->setRenderFilterExempt(true);
		[, $panel] = $this->createPanel('html ', $exempt);
		$inner = new TCallbackResponseWriter();
		$writer = new THtmlWriter($inner);
		$this->renderDeferred($this->filter($panel), $writer);
		$this->assertSame($this->bounded($inner, '<div id="Live">html-f html</div>'), $inner->flush());
		$this->assertFalse($writer->getRenderFilterContext()->getIsCapturing());
	}

	public function testWrittenOutputIsNotFiltered()
	{
		[, $panel] = $this->createPanel('html');
		$inner = new TCallbackResponseWriter();
		$inner->write('html ');
		$this->renderDeferred($this->filter($panel), new THtmlWriter($inner));
		$this->assertSame($this->bounded($inner, 'html <div id="Live">html-f</div>'), $inner->flush());
	}

	public function testAncestorsFilterFragmentsInnermostFirst()
	{
		[, $panel] = $this->createPanel('html');
		$this->nest($this->filter($panel, 'f', $own), true, true, $fragments);
		$inner = new TCallbackResponseWriter();
		$this->renderDeferred($panel, new THtmlWriter($inner));
		$this->assertSame($this->bounded($inner, '<div id="Live">html-top-outer-f</div>'), $inner->flush());
		$this->assertSame([false], $own);
		$this->assertSame([true], $fragments);
	}

	private function child(mixed ...$children): TControl
	{
		$control = new TControl();
		foreach ($children as $child) {
			$control->getControls()->add($child);
		}
		return $control;
	}

	public static function ancestorDepthProvider(): array
	{
		return [
			'outer sees the panel' => [1, 0, 'html-top-outer html-top'],
			'top does not see the panel' => [0, 1, 'html-outer html-outer'],
			'top sees the panel' => [0, 2, 'html-top-outer html-outer'],
		];
	}

	/**
	 * The panel is one level below the outer control and two below the top control.
	 * @dataProvider ancestorDepthProvider
	 */
	public function testAncestorDepthCountsFromAncestor(int $outerDepth, int $topDepth, string $expected)
	{
		[, $panel] = $this->createPanel('html ', $this->child('html'));
		[$outer, $top] = $this->nest($panel);
		$outer->setRenderFilterDepth($outerDepth);
		$top->setRenderFilterDepth($topDepth);
		$inner = new TCallbackResponseWriter();
		$this->renderDeferred($panel, new THtmlWriter($inner));
		$this->assertSame($this->bounded($inner, '<div id="Live">' . $expected . '</div>'), $inner->flush());
	}

	public function testOwnDepthCountsFromControl()
	{
		[, $panel] = $this->createPanel('html ', $this->child('html ', $this->child('html')));
		$this->filter($panel)->setRenderFilterDepth(1);
		$inner = new TCallbackResponseWriter();
		$this->renderDeferred($panel, new THtmlWriter($inner));
		$this->assertSame($this->bounded($inner, '<div id="Live">html-f html-f html</div>'), $inner->flush());
	}

	public function testTermsMarkFragmentToTheirDepthLimit()
	{
		[, $panel] = $this->createPanel('HTML CSS');
		[$ancestor] = $this->enclose($panel, '', '', $this->child());
		$ancestor->onRenderFilter->clear();
		$middle = $this->child();
		$ancestor->getControls()->remove($panel);
		$middle->getControls()->add($panel);
		$ancestor->getControls()->add($middle);
		$html = new TAbbr();
		$html->setTerms('HTML');
		$html->setDepthLimit(1);
		$css = new TAbbr();
		$css->setTerms('CSS');
		$ancestor->getControls()->add($html);
		$ancestor->getControls()->add($css);
		$html->onPreRender(null);
		$css->onPreRender(null);
		$inner = new TCallbackResponseWriter();
		$this->renderDeferred($panel, new THtmlWriter($inner));
		$this->assertSame($this->bounded($inner, '<div id="Live">HTML <abbr>CSS</abbr></div>'), $inner->flush());
	}

	public function testAncestorWithoutFragmentsIsSkipped()
	{
		[, $panel] = $this->createPanel('html');
		$this->nest($panel, false);
		$inner = new TCallbackResponseWriter();
		$this->renderDeferred($panel, new THtmlWriter($inner));
		$this->assertSame($this->bounded($inner, '<div id="Live">html-top</div>'), $inner->flush());
	}

	public function testExemptControlIsHiddenFromAncestors()
	{
		[, $panel] = $this->createPanel('html');
		$this->nest($this->filter($panel));
		$panel->setRenderFilterExempt(true);
		$inner = new TCallbackResponseWriter();
		$this->renderDeferred($panel, new THtmlWriter($inner));
		$this->assertSame($this->bounded($inner, '<div id="Live">html-f</div>'), $inner->flush());
	}

	public function testExemptAncestorHidesFromAncestorsAbove()
	{
		[, $panel] = $this->createPanel('html');
		[$outer] = $this->nest($panel);
		$outer->setRenderFilterExempt(true);
		$inner = new TCallbackResponseWriter();
		$this->renderDeferred($panel, new THtmlWriter($inner));
		$this->assertSame($this->bounded($inner, '<div id="Live">html-outer</div>'), $inner->flush());
	}

	public function testReEnabledControlInsideExemptAncestor()
	{
		[, $panel] = $this->createPanel('html');
		[$outer] = $this->nest($panel);
		$outer->setRenderFilterExempt(true);
		$panel->setRenderFilterExempt(false);
		$inner = new TCallbackResponseWriter();
		$this->renderDeferred($panel, new THtmlWriter($inner));
		$this->assertSame($this->bounded($inner, '<div id="Live">html-top-outer</div>'), $inner->flush());
	}

	public function testExemptDescendantIsHiddenFromAncestors()
	{
		$exempt = new TControl();
		$exempt->getControls()->add('html');
		$exempt->setRenderFilterExempt(true);
		[, $panel] = $this->createPanel('html ', $exempt);
		$this->nest($panel);
		$inner = new TCallbackResponseWriter();
		$writer = new THtmlWriter($inner);
		$this->renderDeferred($panel, $writer);
		$this->assertSame($this->bounded($inner, '<div id="Live">html-top-outer html</div>'), $inner->flush());
		$this->assertFalse($writer->getRenderFilterContext()->getIsCapturing());
	}

	public function testFragmentIsInsideItsContext()
	{
		[, $panel] = $this->createPanel('html');
		[, $seen] = $this->enclose($panel, '<section><p>x</p><pre class="c"><br><img src="i"/>', '</pre></section>');
		$inner = new TCallbackResponseWriter();
		$this->renderDeferred($panel, new THtmlWriter($inner));
		$this->assertSame($this->bounded($inner, '<div id="Live">html-a</div>'), $inner->flush());
		$this->assertMatchesRegularExpression(
			'/^<section><pre class="c"><!--prado-fragment-[0-9a-f]{8}--><div id="Live">html<\/div><!--\/prado-fragment-[0-9a-f]{8}--><\/pre><\/section>$/',
			$seen[0]
		);
	}

	public function testFragmentInsideExemptMarkupIsNotFiltered()
	{
		[, $panel] = $this->createPanel('html');
		$this->enclose($panel, '<div data-prado-filter-exempt="">', '</div>');
		$inner = new TCallbackResponseWriter();
		$this->renderDeferred($panel, new THtmlWriter($inner));
		$this->assertSame($this->bounded($inner, '<div id="Live">html</div>'), $inner->flush());
	}

	public function testFragmentContextOfControls()
	{
		[, $panel] = $this->createPanel('html');
		$code = new THtmlElement();
		$code->setTagName('CODE');
		$exempt = new TControl();
		$exempt->setAttribute(TRenderFilterParameter::EXEMPT_ATTRIBUTE, 'a"b');
		[$ancestor] = $this->enclose($panel, '<p>', '</p>', $code);
		$exempt->getControls()->add($ancestor);
		$ancestor->setRenderFilterFragments(false);
		$plain = new TControl();
		$plain->getControls()->add($exempt);
		$this->assertSame(
			['<div data-prado-filter-exempt="a&quot;b"><code>', '</code></div>'],
			PradoUnit::invoke($this->adapter(), 'getFragmentContext', $plain, $panel)
		);
	}

	public function testFragmentInsideSkippedMarkupIsNotMarked()
	{
		[, $panel] = $this->createPanel('HTML');
		$ancestor = new TControl();
		$abbr = new TAbbr();
		$abbr->setTerms('HTML');
		[$ancestor] = $this->enclose($panel, '<pre>', '</pre>', $ancestor);
		$ancestor->onRenderFilter->clear();
		$ancestor->getControls()->add($abbr);
		$abbr->onPreRender(null);
		$inner = new TCallbackResponseWriter();
		$this->renderDeferred($panel, new THtmlWriter($inner));
		$this->assertSame($this->bounded($inner, '<div id="Live">HTML</div>'), $inner->flush());
	}

	public function testFragmentOutsideSkippedMarkupIsMarked()
	{
		[, $panel] = $this->createPanel('HTML');
		$ancestor = new TControl();
		$abbr = new TAbbr();
		$abbr->setTerms('HTML');
		[$ancestor] = $this->enclose($panel, '<pre>x</pre><p>', '</p>', $ancestor);
		$ancestor->onRenderFilter->clear();
		$ancestor->getControls()->add($abbr);
		$abbr->onPreRender(null);
		$inner = new TCallbackResponseWriter();
		$this->renderDeferred($panel, new THtmlWriter($inner));
		$this->assertSame($this->bounded($inner, '<div id="Live"><abbr>HTML</abbr></div>'), $inner->flush());
	}

	public function testRemovedMarkerLeavesFragmentUnfiltered()
	{
		[, $panel] = $this->createPanel('html');
		[$ancestor] = $this->enclose($panel, '', '');
		$ancestor->onRenderFilter[] = function ($sender, TRenderFilterParameter $param) {
			$param->setFilterText(preg_replace('/<!--\/prado-fragment-[^>]*-->/', '', $param->getFilterText()));
		};
		$inner = new TCallbackResponseWriter();
		$this->renderDeferred($panel, new THtmlWriter($inner));
		$this->assertSame($this->bounded($inner, '<div id="Live">html</div>'), $inner->flush());
	}

	public static function closableTagProvider(): array
	{
		return [
			'kept' => [['pre', '<pre class="x">'], ['pre', '<pre class="x">']],
			'optional end' => [['p', '<p class="x">'], null],
			'optional end, exempt' => [['li', '<li class="x" data-prado-filter-exempt>'], ['div', '<div data-prado-filter-exempt>']],
			'optional end, exempt value' => [['p', "<P DATA-PRADO-FILTER-EXEMPT='false'>"], ['div', "<div DATA-PRADO-FILTER-EXEMPT='false'>"]],
		];
	}

	/**
	 * @dataProvider closableTagProvider
	 */
	public function testClosableTag(array $tag, ?array $expected)
	{
		$this->assertSame($expected, PradoUnit::invoke($this->adapter(), 'getClosableTag', $tag));
	}

	public static function openTagsProvider(): array
	{
		return [
			'nested' => ['<div><p class="x">', [['div', '<div>'], ['p', '<p class="x">']]],
			'closed' => ['<div><b>x</b><i>y</i></div><p>', [['p', '<p>']]],
			'unmatched end' => ['<p></span>', [['p', '<p>']]],
			'end closes inner' => ['<div><p><span></p>', [['div', '<div>']]],
			'void and self-closed' => ['<br><img src="x"><svg/><hr />', []],
			'comment' => ['<!-- <pre> -->', []],
			'raw text' => ['<script>var s = "<pre>";</script><style>p{}</style>', []],
			'uppercase' => ['<PRE>', [['pre', '<PRE>']]],
		];
	}

	/**
	 * @dataProvider openTagsProvider
	 */
	public function testOpenTags(string $markup, array $expected)
	{
		$this->assertSame($expected, PradoUnit::invoke($this->adapter(), 'getOpenTags', $markup));
	}

	public function testOtherWriterRendersUnfiltered()
	{
		[, $panel] = $this->createPanel('html');
		$inner = new TTextWriter();
		$this->renderDeferred($this->filter($panel), new THtmlWriter($inner));
		$this->assertSame('<div id="Live">html</div>', $inner->flush());
	}
}
