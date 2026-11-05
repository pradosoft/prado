<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IDataRenderer;
use Prado\Test\Unit\Harness\Traits\TWebControlRenderTrait;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\TControl;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\TRenderFilterParameter;
use Prado\Web\UI\WebControls\TAbbr;
use Prado\Web\UI\WebControls\TBaseTerm;
use Prado\Web\UI\WebControls\TDfn;
use Prado\Web\UI\WebControls\THtmlElement;
use Prado\Web\UI\WebControls\TPanel;
use Prado\Web\UI\WebControls\TVar;

/**
 * Tests for {@see TBaseTerm} and its controls {@see TAbbr}, {@see TDfn}, and {@see TVar}.
 *
 * The marking tests render a TControl parent, the default target, which renders
 * only its children.
 */
class TBaseTermTest extends TestCase
{
	use TWebControlRenderTrait;

	private const EXPANSION = 'HyperText Markup Language';

	private function abbr(string $text = 'HTML', string $title = self::EXPANSION): TAbbr
	{
		$abbr = new TAbbr();
		$abbr->setText($text);
		$abbr->setTitle($title);
		return $abbr;
	}

	/**
	 * A TControl holding the strings and controls, in order.
	 */
	private function parent(mixed ...$children): TControl
	{
		$parent = new TControl();
		foreach ($children as $child) {
			$parent->getControls()->add($child);
		}
		return $parent;
	}

	/**
	 * Runs the PreRender step and renders the control.
	 */
	private function renderTree(TControl $control): string
	{
		PradoUnit::invoke($control, 'preRenderRecursive');
		$writer = new THtmlWriter();
		$control->renderControl($writer);
		return $writer->flush();
	}

	private function marked(string $term, string $title = self::EXPANSION, string $tag = 'abbr'): string
	{
		return '<' . $tag . ($title === '' ? '' : ' title="' . $title . '"') . '>' . $term . '</' . $tag . '>';
	}

	// ================================================================================
	// Classes
	// ================================================================================

	public static function classProvider(): array
	{
		return [
			'TAbbr' => [TAbbr::class, 'abbr', 0],
			'TDfn' => [TDfn::class, 'dfn', 1],
			'TVar' => [TVar::class, 'var', 0],
		];
	}

	/**
	 * @dataProvider classProvider
	 */
	public function testClass(string $class, string $tag, int $limit)
	{
		$control = new $class();
		$this->assertInstanceOf(TBaseTerm::class, $control);
		$this->assertInstanceOf(THtmlElement::class, $control);
		$this->assertInstanceOf(IDataRenderer::class, $control);
		$this->assertSame($tag, $control->getTagName());
		$this->assertSame($limit, $control->getMarkLimit());
		$control->setText('x');
		$this->assertSame('<' . $tag . '>x</' . $tag . '>', $this->render($control));
	}

	// ================================================================================
	// Properties
	// ================================================================================

	public function testDefaults()
	{
		$abbr = new TAbbr();
		$this->assertSame('', $abbr->getText());
		$this->assertSame('', $abbr->getTitle());
		$this->assertSame([], $abbr->getTerms());
		$this->assertSame('', $abbr->getTargetControl());
		$this->assertTrue($abbr->getMarkOccurrences());
		$this->assertTrue($abbr->getCaseSensitive());
	}

	public function testTextIsData()
	{
		$abbr = new TAbbr();
		$abbr->setData('CSS');
		$this->assertSame('CSS', $abbr->getText());
		$this->assertSame('CSS', $abbr->getData());
	}

	public function testTitleIsToolTip()
	{
		$abbr = new TAbbr();
		$abbr->setTitle(self::EXPANSION);
		$this->assertSame(self::EXPANSION, $abbr->getToolTip());
		$abbr->setToolTip('x');
		$this->assertSame('x', $abbr->getTitle());
	}

	public function testSetTermsParsesString()
	{
		$abbr = new TAbbr();
		$abbr->setTerms('CSS => Cascading Style Sheets, DOM => "Document Object Model, Level 3", URL');
		$this->assertSame(['CSS' => 'Cascading Style Sheets', 'DOM' => 'Document Object Model, Level 3', 'URL'], $abbr->getTerms());
		$abbr->setTerms(['A' => 'b']);
		$this->assertSame(['A' => 'b'], $abbr->getTerms());
	}

	public function testSetTargetControlTrims()
	{
		$abbr = new TAbbr();
		$abbr->setTargetControl(' Box ');
		$this->assertSame('Box', $abbr->getTargetControl());
	}

	public function testSetBooleans()
	{
		$abbr = new TAbbr();
		$abbr->setMarkOccurrences('false');
		$abbr->setCaseSensitive('false');
		$this->assertFalse($abbr->getMarkOccurrences());
		$this->assertFalse($abbr->getCaseSensitive());
	}

	public function testSetMarkLimit()
	{
		$dfn = new TDfn();
		$dfn->setMarkLimit('3');
		$this->assertSame(3, $dfn->getMarkLimit());
		$dfn->setMarkLimit(0);
		$this->assertSame(0, $dfn->getMarkLimit());
	}

	public function testSetMarkLimitNegativeThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TAbbr())->setMarkLimit(-1);
	}

	public function testSetDepthLimit()
	{
		$abbr = new TAbbr();
		$this->assertSame(0, $abbr->getDepthLimit());
		$abbr->setDepthLimit('2');
		$this->assertSame(2, $abbr->getDepthLimit());
	}

	public function testSetDepthLimitNegativeThrows()
	{
		$this->expectException(TInvalidDataValueException::class);
		(new TAbbr())->setDepthLimit(-1);
	}

	// ================================================================================
	// Rendering
	// ================================================================================

	public function testRendersTextEncodedWithTitle()
	{
		$this->assertSame($this->marked('HTML'), $this->render($this->abbr()));
		$this->assertSame('<abbr>&lt;b&gt; &amp; &quot;</abbr>', $this->render($this->abbr('<b> &amp; "', '')));
	}

	public function testRendersBodyWhenTextEmpty()
	{
		$abbr = $this->abbr('');
		$abbr->getControls()->add('<b>HTML</b>');
		$this->assertSame('<abbr title="' . self::EXPANSION . '"><b>HTML</b></abbr>', $this->render($abbr));
	}

	public function testRendersNothingWithoutContent()
	{
		$abbr = new TAbbr();
		$abbr->setTitle('x');
		$this->assertFalse($abbr->getHasContent());
		$this->assertSame('', $this->render($abbr));
	}

	// ================================================================================
	// Terms
	// ================================================================================

	public function testTermIsTextOrBodyText()
	{
		$this->assertSame('HTML', $this->abbr(' HTML ')->getTerm());
		$abbr = new TAbbr();
		$this->assertSame('', $abbr->getTerm());
		$abbr->getControls()->add(' <b>Caf&eacute;</b> ');
		$this->assertSame('Café', $abbr->getTerm());
		$abbr->getControls()->add(new TControl());
		$this->assertSame('', $abbr->getTerm());
	}

	public function testTermTitles()
	{
		$abbr = $this->abbr();
		$abbr->setTerms(['HTML' => 'replaced', 'CSS' => 'Cascading Style Sheets', 'URL', ' ', '' => 'x']);
		$this->assertSame(['HTML' => self::EXPANSION, 'CSS' => 'Cascading Style Sheets', 'URL' => ''], $abbr->getTermTitles());
	}

	public function testTermKey()
	{
		$abbr = new TAbbr();
		$this->assertSame('Render Filter', $abbr->getTermKey("Render \n Filter"));
		$abbr->setCaseSensitive(false);
		$this->assertSame('café au lait', $abbr->getTermKey("CAFÉ au\nLait"));
	}

	// ================================================================================
	// Marking
	// ================================================================================

	public function testMarksOtherOccurrencesInParent()
	{
		$this->assertSame(
			'<p>' . $this->marked('HTML') . ' pages use ' . $this->marked('HTML') . '.</p>',
			$this->renderTree($this->parent('<p>', $this->abbr(), ' pages use HTML.</p>'))
		);
	}

	public function testMarksWholeWordsOnly()
	{
		$this->assertSame(
			$this->marked('HTML') . ' HTML5 xHTML HTML_x HTMLé (' . $this->marked('HTML') . ')',
			$this->renderTree($this->parent($this->abbr(), ' HTML5 xHTML HTML_x HTMLé (HTML)'))
		);
	}

	public function testMarksLongestTermFirst()
	{
		$abbr = $this->abbr();
		$abbr->setTerms(['HTML5' => 'HTML version 5']);
		$this->assertSame(
			$this->marked('HTML') . ' ' . $this->marked('HTML5', 'HTML version 5'),
			$this->renderTree($this->parent($abbr, ' HTML5'))
		);
	}

	public function testMarksTermsWithTitles()
	{
		$abbr = new TAbbr();
		$abbr->setTerms(['CSS' => 'Cascading Style Sheets', 'URL']);
		$this->assertSame(
			'<p>' . $this->marked('CSS', 'Cascading Style Sheets') . ' and ' . $this->marked('URL', '') . '</p>',
			$this->renderTree($this->parent('<p>CSS and URL</p>', $abbr))
		);
	}

	public function testTitleIsEncoded()
	{
		$dom = new \DOMDocument();
		$dom->loadHTML('<?xml encoding="UTF-8">' . $this->renderTree($this->parent($this->abbr('HTML', 'a & "b"'), ' <b>HTML</b>')));
		$titles = array_map(fn ($abbr) => $abbr->getAttribute('title'), iterator_to_array($dom->getElementsByTagName('abbr')));
		$this->assertSame(['a & "b"', 'a & "b"'], $titles);
	}

	public function testMultiWordTermMatchesAnyWhitespace()
	{
		$dfn = new TDfn();
		$dfn->setTerms('render filter');
		$this->assertSame(
			'<p>A <dfn>render' . "\n  " . 'filter</dfn>, a render filter</p>',
			$this->renderTree($this->parent("<p>A render\n  filter, a render filter</p>", $dfn))
		);
	}

	public static function skippedProvider(): array
	{
		return [
			'code' => ['<code>HTML</code>'],
			'pre' => ['<pre><b>HTML</b></pre>'],
			'kbd' => ['<kbd>HTML</kbd>'],
			'samp' => ['<samp>HTML</samp>'],
			'var' => ['<var>HTML</var>'],
			'dfn' => ['<dfn>HTML</dfn>'],
			'existing abbr' => ['<abbr title="other">HTML</abbr>'],
			'script' => ['<script>var HTML = "<b>HTML</b>";</script>'],
			'style' => ['<style>.HTML { color: red; }</style>'],
			'textarea' => ['<textarea>HTML</textarea>'],
			'select option' => ['<select><option>HTML</option></select>'],
			'attribute value' => ['<a href="/HTML" title="HTML" data-x=\'HTML\'>x</a>'],
			'comment' => ['<!-- HTML -->'],
			'exempt markup' => ['<p data-prado-filter-exempt="">HTML <b>HTML</b></p>'],
		];
	}

	/**
	 * @dataProvider skippedProvider
	 */
	public function testSkipsText(string $html)
	{
		$this->assertSame($this->marked('HTML') . $html, $this->renderTree($this->parent($this->abbr(), $html)));
	}

	public function testReEnabledMarkupInsideSkippedTagIsMarked()
	{
		$html = '<pre data-prado-filter-exempt><span data-prado-filter-exempt="false">HTML</span></pre><code data-prado-filter-exempt="false">HTML</code>';
		$output = $this->renderTree($this->parent($this->abbr(), $html));
		$this->assertSame(3, substr_count($output, $this->marked('HTML')));
		$this->assertStringContainsString('<pre data-prado-filter-exempt', $output);
	}

	public function testExemptControlIsNotMarked()
	{
		$exempt = $this->parent('HTML');
		$exempt->setRenderFilterExempt(true);
		$this->assertSame($this->marked('HTML') . ' HTML', $this->renderTree($this->parent($this->abbr(), ' ', $exempt)));
	}

	public function testCaseSensitive()
	{
		$this->assertSame($this->marked('HTML') . ' html Html', $this->renderTree($this->parent($this->abbr(), ' html Html')));
		$abbr = $this->abbr();
		$abbr->setCaseSensitive(false);
		$this->assertSame(
			$this->marked('HTML') . ' ' . $this->marked('html') . ' ' . $this->marked('Html'),
			$this->renderTree($this->parent($abbr, ' html Html'))
		);
	}

	public function testUnicodeTerms()
	{
		$abbr = $this->abbr('Café', 'Coffee house');
		$abbr->setCaseSensitive(false);
		$this->assertSame(
			$this->marked('Café', 'Coffee house') . ' ' . $this->marked('CAFÉ', 'Coffee house') . ' Cafés',
			$this->renderTree($this->parent($abbr, ' CAFÉ Cafés'))
		);
	}

	public function testMarkLimit()
	{
		$abbr = new TAbbr();
		$abbr->setTerms(['HTML' => self::EXPANSION, 'CSS' => '']);
		$abbr->setMarkLimit(2);
		$this->assertSame(
			'<abbr>HTML</abbr> ' . $this->marked('HTML') . ' HTML ' . $this->marked('CSS', '') . ' ' . $this->marked('CSS', '') . ' CSS',
			$this->renderTree($this->parent('<abbr>HTML</abbr> HTML HTML CSS', $abbr, ' CSS CSS'))
		);
	}

	/**
	 * A TControl parent holding HTML, the control, a child with HTML, and a
	 * grandchild with HTML.
	 */
	private function levels(TControl ...$terms): TControl
	{
		return $this->parent('HTML ', ...[...$terms, $this->parent('HTML ', $this->parent('HTML'))]);
	}

	public function testDepthLimit()
	{
		$abbr = new TAbbr();
		$abbr->setTerms(['HTML' => self::EXPANSION]);
		$abbr->setDepthLimit(1);
		$this->assertSame($this->marked('HTML') . ' ' . $this->marked('HTML') . ' HTML', $this->renderTree($this->levels($abbr)));
	}

	public function testDepthLimitZeroMarksAllLevels()
	{
		$abbr = new TAbbr();
		$abbr->setTerms(['HTML' => self::EXPANSION]);
		$this->assertSame(
			$this->marked('HTML') . ' ' . $this->marked('HTML') . ' ' . $this->marked('HTML'),
			$this->renderTree($this->levels($abbr))
		);
	}

	public function testDepthLimitRaisesTargetRenderFilterDepth()
	{
		$abbr = new TAbbr();
		$abbr->setTerms(['HTML' => self::EXPANSION]);
		$abbr->setDepthLimit(2);
		$target = $this->levels($abbr);
		$this->renderTree($target);
		$this->assertSame(2, $target->getRenderFilterDepth());

		$deeper = new TAbbr();
		$deeper->setTerms(['HTML' => self::EXPANSION]);
		$deeper->setDepthLimit(1);
		$target = $this->levels($deeper);
		$target->setRenderFilterDepth(3);
		$this->renderTree($target);
		$this->assertSame(3, $target->getRenderFilterDepth());
	}

	public function testTermsMarkTheirOwnDepthLimit()
	{
		$abbr = new TAbbr();
		$abbr->setTerms(['HTML' => self::EXPANSION]);
		$abbr->setDepthLimit(1);
		$css = new TAbbr();
		$css->setTerms(['CSS' => '']);
		$target = $this->parent('HTML CSS ', $abbr, $css, $this->parent('HTML CSS ', $this->parent('HTML CSS')));
		$this->assertSame(
			$this->marked('HTML') . ' ' . $this->marked('CSS', '') . ' ' . $this->marked('HTML') . ' ' . $this->marked('CSS', '') . ' HTML ' . $this->marked('CSS', ''),
			$this->renderTree($target)
		);
	}

	public function testDepthLimitRestoresParameterDepth()
	{
		$abbr = new TAbbr();
		$abbr->setTerms(['HTML' => self::EXPANSION]);
		$abbr->setDepthLimit(1);
		$target = $this->levels($abbr);
		$target->setRenderFilterDepth(2);
		PradoUnit::invoke($target, 'preRenderRecursive');
		$limits = [];
		$target->onRenderFilter[] = function ($sender, TRenderFilterParameter $param) use (&$limits) {
			$limits[] = $param->getDepthLimit();
		};
		$writer = new THtmlWriter();
		$target->renderControl($writer);
		$this->assertSame([2], $limits);
		$this->assertSame($this->marked('HTML') . ' ' . $this->marked('HTML') . ' HTML', $writer->flush());
	}

	public function testDfnMarksFirstOccurrenceOfTerms()
	{
		$dfn = new TDfn();
		$dfn->setTerms('placeholder');
		$this->assertSame(
			'<p>A ' . $this->marked('placeholder', '', 'dfn') . ' and a placeholder.</p>',
			$this->renderTree($this->parent('<p>A placeholder and a placeholder.</p>', $dfn))
		);
	}

	public function testDfnTermInsideTargetIsNotMarkedAgain()
	{
		$dfn = new TDfn();
		$dfn->setText('render filter');
		$this->assertSame([], PradoUnit::invoke($dfn, 'getMarkedTermTitles'));
		$html = ' A render filter.<br/>';
		$this->assertSame('<dfn>render filter</dfn>' . $html, $this->renderTree($this->parent($dfn, $html)));
	}

	public function testVarIsCaseSensitive()
	{
		$var = new TVar();
		$var->setText('x');
		$var->setTerms('y');
		$this->assertSame(
			'<var>x</var> + <var>y</var> = X, box, <var>x</var>',
			$this->renderTree($this->parent($var, ' + y = X, box, x'))
		);
	}

	public function testOutputWithoutMarksIsUnchanged()
	{
		$html = '<span>A</span>  <b>B</b> <br/><pre><b>HTML</b></pre>';
		$this->assertSame($this->marked('HTML') . $html, $this->renderTree($this->parent($this->abbr(), $html)));
	}

	public function testUnmarkedTermPattern()
	{
		$abbr = $this->abbr();
		$pattern = PradoUnit::invoke($abbr, 'getUnmarkedTermPattern', PradoUnit::invoke($abbr, 'getTermExpression', ['HTML']));
		$this->assertSame(0, preg_match($pattern, '<abbr title="HTML">HTML</abbr><code>HTML</code><script>"<b>HTML</b>"</script ><!-- HTML --><a title=HTML>'));
		$this->assertSame(1, preg_match($pattern, '<abbr>HTML</abbr> HTML'));
		$this->assertSame(1, preg_match($pattern, '<pre><b>HTML</b></pre>'));
		$this->assertSame(1, preg_match($pattern, '1 < 2 HTML'));
	}

	// ================================================================================
	// Target
	// ================================================================================

	public function testTargetControl()
	{
		$page = new TPage();
		$box = new TPanel();
		$box->setID('Box');
		$box->getControls()->add('HTML');
		$abbr = $this->abbr();
		$abbr->setTargetControl('Box');
		$page->getControls()->add($box);
		$page->getControls()->add($abbr);
		$page->getControls()->add(' HTML');
		$this->assertSame($box, $abbr->getMarkTarget());
		$this->assertFalse(PradoUnit::invoke($abbr, 'getIsInTarget'));
		PradoUnit::invoke($page, 'preRenderRecursive');
		$writer = new THtmlWriter();
		$box->renderControl($writer);
		$abbr->renderControl($writer);
		$this->assertSame('<div id="Box">' . $this->marked('HTML') . '</div>' . $this->marked('HTML'), $writer->flush());
	}

	public function testTargetControlNotFoundThrows()
	{
		$page = new TPage();
		$abbr = $this->abbr();
		$abbr->setTargetControl('Missing');
		$page->getControls()->add($abbr);
		$this->expectException(TInvalidDataValueException::class);
		$abbr->getMarkTarget();
	}

	public function testTargetIsParentWhenEmpty()
	{
		$abbr = $this->abbr();
		$this->assertNull($abbr->getMarkTarget());
		$parent = $this->parent($abbr);
		$this->assertSame($parent, $abbr->getMarkTarget());
		$this->assertTrue(PradoUnit::invoke($abbr, 'getIsInTarget'));
	}

	public function testMarkOccurrencesFalseMarksNothing()
	{
		$abbr = $this->abbr();
		$abbr->setMarkOccurrences(false);
		$parent = $this->parent($abbr, ' HTML');
		$this->assertSame($this->marked('HTML') . ' HTML', $this->renderTree($parent));
		$this->assertFalse($parent->hasEventHandler('onRenderFilter'));
	}

	public function testInvisibleControlMarksNothing()
	{
		$abbr = $this->abbr();
		$abbr->setVisible(false);
		$this->assertSame(' HTML', $this->renderTree($this->parent($abbr, ' HTML')));
	}

	public function testControlWithoutContentMarksTerms()
	{
		$abbr = new TAbbr();
		$abbr->setTerms(['CSS' => 'Cascading Style Sheets']);
		$this->assertSame($this->marked('CSS', 'Cascading Style Sheets'), $this->renderTree($this->parent('CSS', $abbr)));
	}

	public function testHandlerAttachesOnce()
	{
		$abbr = $this->abbr();
		$parent = $this->parent($abbr, ' HTML');
		$abbr->onPreRender(null);
		$abbr->onPreRender(null);
		$this->assertSame(1, $parent->getEventHandlers('onRenderFilter')->getCount());
	}

	public function testTargetFiltersFragments()
	{
		$abbr = $this->abbr();
		$parent = $this->parent($abbr);
		$abbr->onPreRender(null);
		$this->assertTrue($parent->getRenderFilterFragments());
		$off = $this->abbr();
		$off->setMarkOccurrences(false);
		$other = $this->parent($off);
		$off->onPreRender(null);
		$this->assertFalse($other->getRenderFilterFragments());
	}

	public function testFragmentIsMarkedWithoutMarkLimit()
	{
		$param = new TRenderFilterParameter('<p>HTML</p>');
		$param->setIsFragment(true);
		$this->abbr()->markTerms(null, $param);
		$this->assertSame('<p>' . $this->marked('HTML') . '</p>', $param->getFilterText());
	}

	public function testFragmentIsNotMarkedWithMarkLimit()
	{
		$dfn = new TDfn();
		$dfn->setTerms('placeholder');
		$param = new TRenderFilterParameter('<p>placeholder</p>');
		$param->setIsFragment(true);
		$dfn->markTerms(null, $param);
		$this->assertSame('<p>placeholder</p>', $param->getFilterText());
	}

	public function testMarkTermsWithoutTermsLeavesParameter()
	{
		$param = new TRenderFilterParameter('HTML<br/>');
		(new TAbbr())->markTerms(null, $param);
		$this->assertSame('HTML<br/>', $param->getFilterText());
	}
}
