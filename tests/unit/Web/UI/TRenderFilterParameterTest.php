<?php

use Prado\Prado;
use Prado\Util\TLogger;
use Prado\Web\UI\TRenderFilterParameter;
use PHPUnit\Framework\TestCase;

/**
 * Fixture: overrides `htmlToDom` to simulate a fatal libxml parse failure.
 *
 * `DOMDocument::loadHTML` almost never returns `false` for real HTML input
 * (the HTML parser is extremely lenient).  This subclass reproduces the
 * `false` return path — storing a synthetic `LibXMLError`, logging the
 * warning, and returning `false` — matching the contract of the real method.
 */
class TRenderFilterParameterParseFail extends TRenderFilterParameter
{
	protected function htmlToDom(string $html): DOMDocument|false
	{
		$error = new \LibXMLError();
		$error->level = LIBXML_ERR_FATAL;
		$error->code = 4;
		$error->column = 0;
		$error->message = 'simulated fatal libxml error';
		$error->file = '';
		$error->line = 0;
		$this->storeErrors([$error]);
		Prado::warning('simulated fatal libxml error', static::class);
		return false;
	}
}

/**
 * Comprehensive tests for {@see TRenderFilterParameter}.
 *
 * Sections:
 *   1.  Constructor
 *   2.  getFilterText / setFilterText
 *   3.  getFilterDOM / setFilterDOM
 *   4.  Resource switching (HTML ↔ DOM current resource)
 *   5.  walkElements (recursive, depth, custom start node)
 *   6.  postRaiseEvent DOM→HTML sync
 *   7.  ArrayAccess overrides ('html' and 'dom' keys)
 *   8.  IEventCycleParameter — preRaiseEvent / postRaiseEvent integration
 *   9.  Extra keys via parent ArrayAccess
 *  10.  getFilterErrors / getHasFilterError
 *  11.  ArrayAccess — 'errors' key
 */
class TRenderFilterParameterTest extends TestCase
{
	// ================================================================================
	// Helpers
	// ================================================================================

	private function makeParam(string $html = ''): TRenderFilterParameter
	{
		return new TRenderFilterParameter($html);
	}

	// ================================================================================
	// 0. Constants
	// ================================================================================

	public function testConstantRenderFilterTextValue()
	{
		$this->assertSame('html', TRenderFilterParameter::RENDER_FILTER_TEXT);
	}

	public function testConstantRenderFilterDomValue()
	{
		$this->assertSame('dom', TRenderFilterParameter::RENDER_FILTER_DOM);
	}

	public function testConstantRenderFilterErrorsValue()
	{
		$this->assertSame('errors', TRenderFilterParameter::RENDER_FILTER_ERRORS);
	}

	// ================================================================================
	// 1. Constructor
	// ================================================================================

	public function testConstructorDefaultEmpty()
	{
		$p = $this->makeParam();
		$this->assertSame('', $p->getFilterText());
	}

	public function testConstructorSetsHtml()
	{
		$p = $this->makeParam('<p>hello</p>');
		$this->assertSame('<p>hello</p>', $p->getFilterText());
	}

	public function testConstructorDomNotYetParsed()
	{
		// DOM is parsed lazily; the parameter should start without one
		$p = $this->makeParam('<p>hi</p>');
		// Confirm getFilterText works without triggering DOM parse
		$this->assertSame('<p>hi</p>', $p->getFilterText());
	}

	// ================================================================================
	// 2. getFilterText / setFilterText
	// ================================================================================

	public function testGetFilterTextReturnsConstructorHtml()
	{
		$p = $this->makeParam('<b>bold</b>');
		$this->assertSame('<b>bold</b>', $p->getFilterText());
	}

	public function testSetFilterTextUpdatesText()
	{
		$p = $this->makeParam('<b>bold</b>');
		$p->setFilterText('<i>italic</i>');
		$this->assertSame('<i>italic</i>', $p->getFilterText());
	}

	public function testSetFilterTextInvalidatesDom()
	{
		$p = $this->makeParam('<p>a</p>');
		$dom1 = $p->getFilterDOM(); // parse DOM, make DOM current

		$p->setFilterText('<p>b</p>'); // invalidate DOM
		$dom2 = $p->getFilterDOM(); // re-parse

		$this->assertNotSame($dom1, $dom2, 'setFilterText should discard the cached DOM');
	}

	public function testSetFilterTextMakesStringCurrent()
	{
		$p = $this->makeParam('<p>a</p>');
		$p->getFilterDOM(); // make DOM current
		$p->setFilterText('<p>b</p>'); // switch back to string

		// getFilterText should return the new string, not re-serialise old DOM
		$this->assertStringContainsString('<p>b</p>', $p->getFilterText());
	}

	public function testGetFilterTextSyncsFromDomWhenDomIsCurrent()
	{
		$p = $this->makeParam('<p>original</p>');
		$dom = $p->getFilterDOM();
		$dom->getElementsByTagName('p')->item(0)->textContent = 'modified';

		// getFilterText must serialise DOM to HTML
		$html = $p->getFilterText();
		$this->assertStringContainsString('modified', $html);
	}

	public function testGetFilterTextMakesStringCurrentAfterDomSync()
	{
		$p = $this->makeParam('<p>x</p>');
		$p->getFilterDOM(); // make DOM current
		$p->getFilterText(); // sync back to string

		// Second getFilterText should not re-serialise (DOM is no longer current)
		$p->setFilterText('<p>y</p>');
		$this->assertStringContainsString('y', $p->getFilterText());
	}

	// ================================================================================
	// 3. getFilterDOM / setFilterDOM
	// ================================================================================

	public function testGetFilterDomReturnsDomDocument()
	{
		$p = $this->makeParam('<p>hello</p>');
		$this->assertInstanceOf(DOMDocument::class, $p->getFilterDOM());
	}

	public function testGetFilterDomHasNoWrapperElements()
	{
		$p = $this->makeParam('<p>hello</p>');
		$dom = $p->getFilterDOM();
		// No implicit wrapper elements are added
		$this->assertNull($dom->getElementsByTagName('html')->item(0));
		$this->assertNull($dom->getElementsByTagName('head')->item(0));
		$this->assertNull($dom->getElementsByTagName('body')->item(0));
		// Content element is directly accessible
		$this->assertNotNull($dom->getElementsByTagName('p')->item(0));
	}

	public function testGetFilterDomPreservesContent()
	{
		$p = $this->makeParam('<p>hello world</p>');
		$dom = $p->getFilterDOM();
		$paras = $dom->getElementsByTagName('p');
		$this->assertGreaterThan(0, $paras->length);
		$this->assertStringContainsString('hello world', $paras->item(0)->textContent);
	}

	public function testGetFilterDomReturnsSameInstanceOnSecondCall()
	{
		$p = $this->makeParam('<p>x</p>');
		$this->assertSame($p->getFilterDOM(), $p->getFilterDOM());
	}

	public function testSetFilterDomReplacesDom()
	{
		$p = $this->makeParam('<p>old</p>');
		$new = new DOMDocument('1.0', 'UTF-8');
		$new->loadHTML('<html><body><span>new</span></body></html>');

		$p->setFilterDOM($new);
		$this->assertSame($new, $p->getFilterDOM());
	}

	public function testSetFilterDomMakesDomCurrent()
	{
		$p = $this->makeParam('<p>old</p>');
		$new = new DOMDocument('1.0', 'UTF-8');
		$new->loadHTML('<html><body><span>injected</span></body></html>');
		$p->setFilterDOM($new);

		// getFilterText must serialise the injected DOM
		$html = $p->getFilterText();
		$this->assertStringContainsString('injected', $html);
		$this->assertStringNotContainsString('old', $html);
	}

	public function testGetFilterDomEmptyHtmlReturnsEmptyDoc()
	{
		$p = $this->makeParam('');
		$dom = $p->getFilterDOM();
		$this->assertInstanceOf(DOMDocument::class, $dom);
		// Empty HTML produces a document with no child nodes
		$this->assertSame(0, $dom->childNodes->length);
	}

	// ================================================================================
	// 4. Resource switching
	// ================================================================================

	public function testHtmlToDomToHtmlRoundTrip()
	{
		$original = '<p>round <strong>trip</strong></p>';
		$p = $this->makeParam($original);
		$p->getFilterDOM(); // switch to DOM
		$html = $p->getFilterText(); // switch back to string
		$this->assertStringContainsString('<strong>trip</strong>', $html);
	}

	public function testMalformedHtmlIsParsedLeniently()
	{
		// libxml's HTML parser is intentionally lenient — unclosed tags,
		// missing quotes, etc. are silently recovered.  getFilterDOM() must
		// return a usable DOMDocument rather than throwing.
		$p = $this->makeParam('<p>unclosed <b>bold');
		$dom = $p->getFilterDOM();
		$this->assertInstanceOf(DOMDocument::class, $dom);
		// The content should be accessible via the DOM.
		$this->assertGreaterThan(0, $dom->getElementsByTagName('b')->length);
	}

	public function testFatalParseFailureReturnsFalse()
	{
		// A fatal libxml parse failure (loadHTML returning false) must cause
		// getFilterDOM() to return false — no exception, no silent empty DOM.
		$p = new TRenderFilterParameterParseFail('<p>any html</p>');
		$this->assertFalse($p->getFilterDOM());
	}

	public function testFatalParseFailureLogsWarning()
	{
		// A fatal parse failure must emit a WARNING-level log entry so the cause
		// is visible in the application log.
		$logger = Prado::getLogger();
		$logger->deleteLogs(TLogger::WARNING);

		$p = new TRenderFilterParameterParseFail('<p>any html</p>');
		$p->getFilterDOM();

		$logs = $logger->getLogs(TLogger::WARNING);
		$this->assertNotEmpty($logs, 'A WARNING must be logged when DOM parse fails');
		$this->assertStringContainsString('simulated', $logs[0][0]);
		$this->assertSame(TLogger::WARNING, $logs[0][1]);
	}

	public function testFatalParseFailurePreservesHtmlString()
	{
		// When getFilterDOM() returns false, the original HTML string must be
		// left intact — _domCurrent must not be set, so no spurious serialisation.
		$p = new TRenderFilterParameterParseFail('<p>keep me</p>');
		$p->getFilterDOM(); // returns false; HTML must remain current
		$this->assertSame('<p>keep me</p>', $p->getFilterText());
	}

	public function testFatalParseFailureReturnsFalseOnSubsequentCall()
	{
		// Once _dom is false, repeated calls must return false without re-parsing.
		$p = new TRenderFilterParameterParseFail('<p>x</p>');
		$this->assertFalse($p->getFilterDOM());
		$this->assertFalse($p->getFilterDOM());
	}

	public function testFatalParseFailureResetAfterSetFilterText()
	{
		// setFilterText resets _dom to null, so a fresh parse attempt is made.
		// The fixture always returns false, so we verify false is returned again
		// (not a stale DOMDocument from a previous successful parse).
		$p = new TRenderFilterParameterParseFail('<p>original</p>');
		$p->getFilterDOM(); // → false
		$p->setFilterText('<p>new</p>');
		$this->assertFalse($p->getFilterDOM());
		$this->assertSame('<p>new</p>', $p->getFilterText());
	}

	public function testDomModificationsReflectedInHtml()
	{
		$p = $this->makeParam('<ul><li>a</li><li>b</li></ul>');
		$dom = $p->getFilterDOM();

		// Remove the first <li>
		$ul = $dom->getElementsByTagName('ul')->item(0);
		$li = $dom->getElementsByTagName('li')->item(0);
		$ul->removeChild($li);

		$html = $p->getFilterText();
		$this->assertStringContainsString('<li>b</li>', $html);
		$this->assertStringNotContainsString('<li>a</li>', $html);
	}

	public function testSwitchHtmlDomHtmlPreservesLatestHtml()
	{
		$p = $this->makeParam('<p>first</p>');
		$p->getFilterDOM();               // switch to DOM
		$p->setFilterText('<p>second</p>'); // switch back to string
		$this->assertStringContainsString('second', $p->getFilterText());
		$this->assertStringNotContainsString('first', $p->getFilterText());
	}

	public function testMultipleHandlersDomThenString()
	{
		// Simulate two handlers: first uses DOM, second uses string API
		$p = $this->makeParam('<p>x</p>');

		// Handler 1: add attribute via DOM
		$dom = $p->getFilterDOM();
		$dom->getElementsByTagName('p')->item(0)->setAttribute('id', 'h1');

		// Handler 2: read via getFilterText (syncs DOM→HTML)
		$html = $p->getFilterText();
		$this->assertStringContainsString('id="h1"', $html);

		// Handler 2: append text via setFilterText
		$p->setFilterText($html . '<hr>');
		$final = $p->getFilterText();
		$this->assertStringContainsString('<hr>', $final);
		$this->assertStringContainsString('id="h1"', $final);
	}

	public function testMultipleHandlersStringThenDom()
	{
		// Handler 1 modifies HTML via array-access, Handler 2 reads via DOM
		$p = $this->makeParam('<p>hello</p>');

		// Handler 1
		$p->setFilterText('<p>handler1</p>');

		// Handler 2: DOM should reflect handler 1's change
		$dom = $p->getFilterDOM();
		$paras = $dom->getElementsByTagName('p');
		$this->assertStringContainsString('handler1', $paras->item(0)->textContent);
	}

	// ================================================================================
	// 5. walkElements
	// ================================================================================

	public function testWalkElementsVisitsAllElements()
	{
		$p = $this->makeParam('<div><p>a</p><span>b</span></div>');
		$tags = [];
		$p->walkElements(function (DOMElement $el) use (&$tags) {
			$tags[] = $el->tagName;
		});

		$this->assertContains('div', $tags);
		$this->assertContains('p', $tags);
		$this->assertContains('span', $tags);
		// No implicit wrapper elements
		$this->assertNotContains('html', $tags);
		$this->assertNotContains('head', $tags);
		$this->assertNotContains('body', $tags);
	}

	public function testWalkElementsDepthFirst()
	{
		$p = $this->makeParam('<div><p><strong>deep</strong></p></div>');
		$order = [];
		$p->walkElements(function (DOMElement $el) use (&$order) {
			$order[] = $el->tagName;
		});

		// Pre-order: div → p → strong
		$divIdx = array_search('div', $order);
		$pIdx = array_search('p', $order);
		$strongIdx = array_search('strong', $order);
		$this->assertLessThan($pIdx, $divIdx);
		$this->assertLessThan($strongIdx, $pIdx);
	}

	public function testWalkElementsPassesParamToCallback()
	{
		$p = $this->makeParam('<img src="test.png">');
		$receivedParam = null;
		$p->walkElements(function (DOMElement $el, $param) use (&$receivedParam) {
			$receivedParam = $param;
		});
		$this->assertSame($p, $receivedParam);
	}

	public function testWalkElementsModifyDomInCallback()
	{
		$p = $this->makeParam('<img src="a.png"><img src="b.png">');
		$p->walkElements(function (DOMElement $el, $param) {
			if ($el->tagName === 'img' && !$el->hasAttribute('alt')) {
				$el->setAttribute('alt', '');
			}
		});
		$html = $p->getFilterText();
		$this->assertEquals(2, substr_count($html, 'alt=""'));
	}

	public function testWalkElementsMakesDomCurrent()
	{
		$p = $this->makeParam('<p>text</p>');
		$p->walkElements(function (DOMElement $el, $param) {
			// no-op
		});
		// After walkElements, DOM should be current;
		// getFilterText should serialise and return body content
		$html = $p->getFilterText();
		$this->assertStringContainsString('<p>text</p>', $html);
	}

	public function testWalkElementsEmptyHtmlDoesNotThrow()
	{
		$p = $this->makeParam('');
		$tags = [];
		$p->walkElements(function (DOMElement $el, $param) use (&$tags) {
			$tags[] = $el->tagName;
		});
		$this->assertSame([], $tags, 'Empty HTML should produce no elements to walk');
	}

	public function testWalkElementsWhenDomParseFails()
	{
		// When getFilterDOM() returns false, walkElements must be a no-op.
		$p = new TRenderFilterParameterParseFail('<p>x</p>');
		$called = false;
		$p->walkElements(function (DOMElement $el) use (&$called) {
			$called = true;
		});
		$this->assertFalse($called, 'walkElements must not invoke the callback when DOM parse fails');
	}

	public function testWalkElementsNonRecursiveVisitsOnlyTopLevel()
	{
		$p = $this->makeParam('<div><p>nested</p></div><span>sibling</span>');
		$tags = [];
		$p->walkElements(function (DOMElement $el) use (&$tags) {
			$tags[] = $el->tagName;
		}, null, false);

		$this->assertContains('div', $tags);
		$this->assertContains('span', $tags);
		$this->assertNotContains('p', $tags, 'Non-recursive walk must not descend into children');
	}

	public function testWalkElementsDepthParameterPassedToCallback()
	{
		$p = $this->makeParam('<div><p><strong>deep</strong></p></div>');
		$depths = [];
		$p->walkElements(function (DOMElement $el, $param, int $depth) use (&$depths) {
			$depths[$el->tagName] = $depth;
		});

		$this->assertSame(0, $depths['div']);
		$this->assertSame(1, $depths['p']);
		$this->assertSame(2, $depths['strong']);
	}

	public function testWalkElementsNonRecursiveWithCustomNode()
	{
		$p = $this->makeParam('<div><p><strong>deep</strong></p></div>');
		$dom = $p->getFilterDOM();
		$div = $dom->getElementsByTagName('div')->item(0);

		$tags = [];
		$p->walkElements(function (DOMElement $el) use (&$tags) {
			$tags[] = $el->tagName;
		}, $div, false);

		$this->assertContains('p', $tags);
		$this->assertNotContains('strong', $tags, 'Non-recursive walk from div must not descend into p');
	}

	public function testWalkElementsDepthResetsForCustomStartNode()
	{
		// Depth is always relative to the starting node, not the document root
		$p = $this->makeParam('<div><p><strong>deep</strong></p></div>');
		$dom = $p->getFilterDOM();
		$div = $dom->getElementsByTagName('div')->item(0);

		$depths = [];
		$p->walkElements(function (DOMElement $el, $param, int $depth) use (&$depths) {
			$depths[$el->tagName] = $depth;
		}, $div);

		$this->assertSame(0, $depths['p'],      'p is a direct child of the custom start node');
		$this->assertSame(1, $depths['strong'], 'strong is one level below p');
	}

	public function testWalkElementsCustomStartNode()
	{
		$p = $this->makeParam('<div><p>inside</p></div><span>outside</span>');
		$dom = $p->getFilterDOM();
		$div = $dom->getElementsByTagName('div')->item(0);

		$tags = [];
		$p->walkElements(function (DOMElement $el) use (&$tags) {
			$tags[] = $el->tagName;
		}, $div);

		$this->assertContains('p', $tags);
		$this->assertNotContains('span', $tags, 'walkElements with custom node should not leave the subtree');
	}

	public function testWalkElementsFullHierarchySnapshotBeforeCallbacks()
	{
		// The entire visit list is captured before the first callback fires.
		// A child appended to an already-snapshotted-but-not-yet-visited element
		// during the walk must NOT appear in the visited set.
		$p = $this->makeParam('<ul><li>a</li><li>b</li></ul>');
		$tags = [];
		$p->walkElements(function (DOMElement $el) use (&$tags, $p) {
			$tags[] = $el->tagName;
			// When we visit the <ul>, append a new <li> child.
			// With a full pre-snapshot the two original <li> children are already
			// in the visit list; the new one must not be visited.
			if ($el->tagName === 'ul') {
				$extra = $el->ownerDocument->createElement('li');
				$extra->textContent = 'injected';
				$el->appendChild($extra);
			}
		});

		$liCount = count(array_filter($tags, fn($t) => $t === 'li'));
		$this->assertSame(2, $liCount, 'Only the two original <li> elements should be visited; the injected one must not appear');
	}

	public function testWalkElementsRemovingNodeDuringWalkDoesNotCrash()
	{
		// Removing a sibling that has already been snapshotted must not cause
		// errors, and the removed element must still have been visited.
		$p = $this->makeParam('<div><p id="a">a</p><p id="b">b</p></div>');
		$visited = [];
		$p->walkElements(function (DOMElement $el) use (&$visited) {
			$visited[] = $el->getAttribute('id') ?: $el->tagName;
			// When visiting the first <p>, remove the second one from the DOM.
			if ($el->getAttribute('id') === 'a') {
				$sibling = $el->nextSibling;
				while ($sibling && !($sibling instanceof DOMElement)) {
					$sibling = $sibling->nextSibling;
				}
				if ($sibling instanceof DOMElement) {
					$sibling->parentNode->removeChild($sibling);
				}
			}
		});

		// Both <p> elements were in the snapshot, so both must have been visited
		// even though one was removed during the walk.
		$this->assertContains('a', $visited);
		$this->assertContains('b', $visited);
	}

	// ================================================================================
	// 6. postRaiseEvent DOM→HTML sync
	// ================================================================================

	public function testPostRaiseEventSyncsDomToHtmlWhenDomCurrent()
	{
		$p = $this->makeParam('<p>before</p>');
		$dom = $p->getFilterDOM(); // make DOM current
		$dom->getElementsByTagName('p')->item(0)->textContent = 'after';

		// Simulate what raiseEvent does after handlers complete
		$p->postRaiseEvent([], 'onrenderfilter', null, $p, null, null);

		$this->assertStringContainsString('after', $p->getFilterText());
	}

	public function testPostRaiseEventDoesNothingWhenStringIsCurrent()
	{
		$p = $this->makeParam('<p>original</p>');
		// Do not access DOM — string remains current
		$p->postRaiseEvent([], 'onrenderfilter', null, $p, null, null);

		$this->assertSame('<p>original</p>', $p->getFilterText());
	}

	public function testPostRaiseEventMakesStringCurrentAfterSync()
	{
		$p = $this->makeParam('<p>x</p>');
		$p->getFilterDOM(); // make DOM current
		$p->postRaiseEvent([], 'onrenderfilter', null, $p, null, null);

		// After postRaiseEvent, string should be current; a second call should be a no-op
		$html1 = $p->getFilterText();
		$html2 = $p->getFilterText(); // should not re-sync
		$this->assertSame($html1, $html2);
	}

	public function testPostRaiseEventWithNullDomDoesNothing()
	{
		$p = $this->makeParam('<p>safe</p>');
		// Never call getFilterDOM() — _dom stays null, _domCurrent stays false.
		// postRaiseEvent is a no-op when DOM is not the current resource.
		unset($p[TRenderFilterParameter::RENDER_FILTER_DOM]); // harmless no-op here
		$p->postRaiseEvent([], 'onrenderfilter', null, $p, null, null);
		$this->assertSame('<p>safe</p>', $p->getFilterText());
	}

	// ================================================================================
	// 7. ArrayAccess overrides
	// ================================================================================

	public function testOffsetExistsHtmlAlwaysTrue()
	{
		$p = $this->makeParam('');
		$this->assertTrue(isset($p[TRenderFilterParameter::RENDER_FILTER_TEXT]));
	}

	public function testOffsetExistsDomAlwaysTrue()
	{
		$p = $this->makeParam('');
		$this->assertTrue(isset($p[TRenderFilterParameter::RENDER_FILTER_DOM]));
	}

	public function testOffsetGetHtmlCallsGetFilterText()
	{
		$p = $this->makeParam('<p>test</p>');
		$this->assertSame('<p>test</p>', $p[TRenderFilterParameter::RENDER_FILTER_TEXT]);
	}

	public function testOffsetGetDomCallsGetFilterDom()
	{
		$p = $this->makeParam('<p>test</p>');
		$dom = $p[TRenderFilterParameter::RENDER_FILTER_DOM];
		$this->assertInstanceOf(DOMDocument::class, $dom);
	}

	public function testOffsetSetHtmlCallsSetFilterText()
	{
		$p = $this->makeParam('<p>old</p>');
		$p[TRenderFilterParameter::RENDER_FILTER_TEXT] = '<p>new</p>';
		$this->assertStringContainsString('new', $p->getFilterText());
	}

	public function testOffsetSetDomCallsSetFilterDom()
	{
		$p = $this->makeParam('<p>old</p>');
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->loadHTML('<html><body><span>injected</span></body></html>');
		$p[TRenderFilterParameter::RENDER_FILTER_DOM] = $dom;
		$this->assertSame($dom, $p->getFilterDOM());
	}

	public function testOffsetSetDomIgnoresNonDomDocument()
	{
		$p = $this->makeParam('<p>safe</p>');
		$p[TRenderFilterParameter::RENDER_FILTER_DOM] = 'not a dom'; // should be ignored
		// HTML should be unchanged; DOM was not set
		$this->assertStringContainsString('<p>safe</p>', $p->getFilterText());
	}

	public function testOffsetUnsetHtmlClearsToEmpty()
	{
		$p = $this->makeParam('<p>content</p>');
		unset($p[TRenderFilterParameter::RENDER_FILTER_TEXT]);
		$this->assertSame('', $p->getFilterText());
	}

	public function testOffsetUnsetDomDiscardsCachedDom()
	{
		$p = $this->makeParam('<p>x</p>');
		$p->getFilterDOM(); // parse — _domCurrent = true
		unset($p[TRenderFilterParameter::RENDER_FILTER_DOM]); // discard
		// HTML should still be available
		$this->assertStringContainsString('<p>x</p>', $p->getFilterText());
	}

	public function testOffsetUnsetDomCommitsPendingDomChangesToHtml()
	{
		// If DOM is the current resource (modified after getFilterDOM()),
		// unset($param['dom']) must serialise DOM→HTML before discarding,
		// so that getFilterText() returns the modified content, not the original.
		$p = $this->makeParam('<p>original</p>');
		$dom = $p->getFilterDOM(); // _domCurrent = true
		// Modify the DOM in place.
		$dom->getElementsByTagName('p')->item(0)->textContent = 'modified';
		// Discard DOM — must commit the modification to HTML first.
		unset($p[TRenderFilterParameter::RENDER_FILTER_DOM]);
		$this->assertStringContainsString('modified', $p->getFilterText());
		$this->assertStringNotContainsString('original', $p->getFilterText());
	}

	public function testOffsetUnsetDomWhenHtmlCurrentLeavesHtmlUnchanged()
	{
		// If HTML is already the current resource (_domCurrent = false),
		// unset($param['dom']) must not disturb the HTML string.
		$p = $this->makeParam('<p>keep</p>');
		// Do NOT call getFilterDOM() — _domCurrent stays false.
		unset($p[TRenderFilterParameter::RENDER_FILTER_DOM]);
		$this->assertStringContainsString('<p>keep</p>', $p->getFilterText());
	}

	public function testArrayAccessHtmlRoundTrip()
	{
		$p = $this->makeParam('original');
		$p[TRenderFilterParameter::RENDER_FILTER_TEXT] = strtoupper($p[TRenderFilterParameter::RENDER_FILTER_TEXT]);
		$this->assertSame('ORIGINAL', $p[TRenderFilterParameter::RENDER_FILTER_TEXT]);
	}

	// ================================================================================
	// 8. IEventCycleParameter integration
	// ================================================================================

	public function testIsIEventCycleParameter()
	{
		$p = $this->makeParam('');
		$this->assertInstanceOf(\Prado\IEventCycleParameter::class, $p);
	}

	public function testPreRaiseEventIsCallable()
	{
		$p = $this->makeParam('');
		// preRaiseEvent is inherited from TEventParameter and is a no-op stub; must not throw
		$p->preRaiseEvent('onrenderfilter', null, $p, null, null);
		$this->assertTrue(true); // reached here without exception
	}

	public function testPostRaiseEventCalledByRaiseEvent()
	{
		// Set up a component that raises onRenderFilter
		$component = new \Prado\Web\UI\TControl();
		$component->attachEventHandler('onRenderFilter', function ($sender, $param) {
			$dom = $param->getFilterDOM(); // make DOM current
			$dom->getElementsByTagName('p')->item(0)->textContent = 'via raiseEvent';
		});

		$p = $this->makeParam('<p>original</p>');
		$component->raiseEvent('onRenderFilter', $component, $p);

		// postRaiseEvent should have serialized DOM → HTML
		$this->assertStringContainsString('via raiseEvent', $p->getFilterText());
	}

	// ================================================================================
	// 9. Extra keys via parent ArrayAccess
	// ================================================================================

	public function testExtraKeyStoredAndRetrieved()
	{
		$p = $this->makeParam('<p>x</p>');
		$p['my-flag'] = true;
		$this->assertTrue($p['my-flag']);
	}

	public function testExtraKeyUnset()
	{
		$p = $this->makeParam('');
		$p['temp'] = 'data';
		unset($p['temp']);
		$this->assertFalse(isset($p['temp']));
	}

	public function testExtraKeyDoesNotInterfereWithHtml()
	{
		$p = $this->makeParam('<p>safe</p>');
		$p['extra'] = 'value';
		$this->assertStringContainsString('<p>safe</p>', $p->getFilterText());
	}

	public function testOffsetExistsFalseForUnknownKey()
	{
		$p = $this->makeParam('<p>x</p>');
		$this->assertFalse(isset($p['never-set-key']));
	}

	public function testOffsetGetUnknownKeyReturnsNull()
	{
		$p = $this->makeParam('');
		$this->assertNull($p['no-such-key']);
	}

	public function testOffsetSetHtmlCastsNonStringToString()
	{
		// offsetSet for the html key casts the value to string.
		$p = $this->makeParam('');
		$p[TRenderFilterParameter::RENDER_FILTER_TEXT] = 42;
		$this->assertSame('42', $p->getFilterText());
	}

	public function testSetFilterDomWithoutPriorParse()
	{
		// setFilterDOM on a fresh param (no prior getFilterDOM call) must still
		// make DOM current and serialise correctly on getFilterText.
		$p = $this->makeParam('<p>old</p>');
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->loadHTML('<html><body><em>fresh</em></body></html>');
		$p->setFilterDOM($dom);
		$this->assertStringContainsString('fresh', $p->getFilterText());
		$this->assertStringNotContainsString('old', $p->getFilterText());
	}

	public function testSetFilterTextEmptyStringStoresEmpty()
	{
		$p = $this->makeParam('<p>content</p>');
		$p->setFilterText('');
		$this->assertSame('', $p->getFilterText());
	}

	public function testSetFilterTextAfterDomModificationDiscardsDom()
	{
		// setFilterText must discard the DOM so the next getFilterDOM re-parses.
		$p = $this->makeParam('<p>a</p>');
		$dom1 = $p->getFilterDOM();
		$p->setFilterText('<p>b</p>');
		$dom2 = $p->getFilterDOM();
		$this->assertNotSame($dom1, $dom2);
		$this->assertStringContainsString('b', $dom2->getElementsByTagName('p')->item(0)->textContent);
	}

	public function testGetFilterDomMakesDomCurrentEvenAfterTextSync()
	{
		// After getFilterText() syncs DOM→string (clearing _domCurrent), calling
		// getFilterDOM() again must re-set _domCurrent = true so a subsequent
		// DOM modification is not lost on the next getFilterText() call.
		$p = $this->makeParam('<p>start</p>');
		$p->getFilterDOM();           // parse, _domCurrent = true
		$p->getFilterText();          // sync, _domCurrent = false
		$dom = $p->getFilterDOM();    // re-assert _domCurrent = true
		$dom->getElementsByTagName('p')->item(0)->textContent = 'end';
		$this->assertStringContainsString('end', $p->getFilterText());
	}

	public function testOffsetUnsetHtmlAlsoDiscardsDom()
	{
		// After unset(html), the DOM must be discarded too (setFilterText('') is called).
		$p = $this->makeParam('<p>x</p>');
		$dom1 = $p->getFilterDOM();
		unset($p[TRenderFilterParameter::RENDER_FILTER_TEXT]);
		$dom2 = $p->getFilterDOM(); // re-parsed from ''
		$this->assertNotSame($dom1, $dom2);
		$this->assertSame(0, $dom2->childNodes->length);
	}

	public function testWalkElementsTextNodesNotVisited()
	{
		// Only DOMElement nodes are visited, not text nodes.
		$p = $this->makeParam('plain text<p>element</p>');
		$tags = [];
		$p->walkElements(function (DOMElement $el) use (&$tags) {
			$tags[] = $el->tagName;
		});
		$this->assertContains('p', $tags);
		$this->assertNotContains('#text', $tags);
	}

	public function testMultipleTopLevelElementsAllVisited()
	{
		$p = $this->makeParam('<p>a</p><div>b</div><span>c</span>');
		$tags = [];
		$p->walkElements(function (DOMElement $el) use (&$tags) {
			$tags[] = $el->tagName;
		});
		$this->assertContains('p', $tags);
		$this->assertContains('div', $tags);
		$this->assertContains('span', $tags);
	}

	public function testSetFilterTextMakesGetFilterDomReturnFreshParse()
	{
		$p = $this->makeParam('<p>v1</p>');
		$p->getFilterDOM(); // parse v1
		$p->setFilterText('<p>v2</p>'); // invalidate
		$dom = $p->getFilterDOM();
		$this->assertSame('v2', $dom->getElementsByTagName('p')->item(0)->textContent);
	}

	// ================================================================================
	// 10. getFilterErrors / getHasFilterError
	// ================================================================================

	public function testGetFilterErrorsNullOnFreshInstance()
	{
		$p = $this->makeParam('<p>hello</p>');
		$this->assertNull($p->getFilterErrors());
	}

	public function testGetHasFilterErrorFalseOnFreshInstance()
	{
		$p = $this->makeParam('<p>hello</p>');
		$this->assertFalse($p->getHasFilterError());
	}

	public function testGetFilterErrorsNullAfterCleanParse()
	{
		// Well-formed HTML produces no libxml errors — null, not an empty array.
		$p = $this->makeParam('<p><strong>clean</strong></p>');
		$p->getFilterDOM();
		$this->assertNull($p->getFilterErrors());
		$this->assertFalse($p->getHasFilterError());
	}

	public function testGetFilterErrorsNonEmptyAfterFatalParseFail()
	{
		// Fixture sets a synthetic LibXMLError in $_parseErrors.
		$p = new TRenderFilterParameterParseFail('<p>bad</p>');
		$p->getFilterDOM(); // triggers fixture
		$errors = $p->getFilterErrors();
		$this->assertNotEmpty($errors);
		$this->assertInstanceOf(\LibXMLError::class, $errors[0]);
	}

	public function testGetHasFilterErrorTrueAfterFatalParseFail()
	{
		$p = new TRenderFilterParameterParseFail('<p>bad</p>');
		$p->getFilterDOM();
		$this->assertTrue($p->getHasFilterError());
	}

	public function testGetFilterErrorsRetainedAcrossMultipleGetFilterDomCalls()
	{
		// Once stored, errors persist across subsequent getFilterDOM() calls
		// because ensureFilterDOM() is a no-op when _dom is already set.
		$p = new TRenderFilterParameterParseFail('<p>bad</p>');
		$p->getFilterDOM();
		$p->getFilterDOM(); // no re-parse
		$this->assertNotEmpty($p->getFilterErrors());
	}

	public function testGetFilterErrorsClearedBySetFilterText()
	{
		$p = new TRenderFilterParameterParseFail('<p>bad</p>');
		$p->getFilterDOM(); // populate errors
		$p->setFilterText('<p>new</p>');
		$this->assertNull($p->getFilterErrors());
		$this->assertFalse($p->getHasFilterError());
	}

	public function testGetFilterErrorsClearedBySetFilterDOM()
	{
		$p = new TRenderFilterParameterParseFail('<p>bad</p>');
		$p->getFilterDOM(); // populate errors
		$dom = new DOMDocument('1.0', 'UTF-8');
		$p->setFilterDOM($dom);
		$this->assertNull($p->getFilterErrors());
		$this->assertFalse($p->getHasFilterError());
	}

	public function testGetFilterErrorsErrorLevelAndMessageRetained()
	{
		// Verify the LibXMLError object fields are preserved as-is.
		$p = new TRenderFilterParameterParseFail('<p>bad</p>');
		$p->getFilterDOM();
		$error = $p->getFilterErrors()[0];
		$this->assertSame(LIBXML_ERR_FATAL, $error->level);
		$this->assertStringContainsString('simulated', $error->message);
	}

	// ================================================================================
	// 11. ArrayAccess — 'errors' key
	// ================================================================================

	public function testOffsetExistsErrorsFalseWhenNoErrors()
	{
		$p = $this->makeParam('<p>clean</p>');
		$this->assertFalse(isset($p[TRenderFilterParameter::RENDER_FILTER_ERRORS]));
	}

	public function testOffsetExistsErrorsTrueWhenHasErrors()
	{
		$p = new TRenderFilterParameterParseFail('<p>bad</p>');
		$p->getFilterDOM();
		$this->assertTrue(isset($p[TRenderFilterParameter::RENDER_FILTER_ERRORS]));
	}

	public function testOffsetGetErrorsReturnsNullWhenNone()
	{
		$p = $this->makeParam('<p>clean</p>');
		$this->assertNull($p[TRenderFilterParameter::RENDER_FILTER_ERRORS]);
	}

	public function testOffsetGetErrorsReturnsErrorsAfterFailedParse()
	{
		$p = new TRenderFilterParameterParseFail('<p>bad</p>');
		$p->getFilterDOM();
		$errors = $p[TRenderFilterParameter::RENDER_FILTER_ERRORS];
		$this->assertNotEmpty($errors);
		$this->assertInstanceOf(\LibXMLError::class, $errors[0]);
	}

	public function testOffsetSetErrorsIsNoOp()
	{
		// Assigning to the 'errors' key must not change the stored errors.
		$p = new TRenderFilterParameterParseFail('<p>bad</p>');
		$p->getFilterDOM();
		$before = $p->getFilterErrors();
		$p[TRenderFilterParameter::RENDER_FILTER_ERRORS] = [];
		$this->assertSame($before, $p->getFilterErrors());
	}

	public function testOffsetUnsetErrorsClearsErrors()
	{
		$p = new TRenderFilterParameterParseFail('<p>bad</p>');
		$p->getFilterDOM();
		$this->assertTrue($p->getHasFilterError());
		unset($p[TRenderFilterParameter::RENDER_FILTER_ERRORS]);
		$this->assertNull($p->getFilterErrors());
		$this->assertFalse($p->getHasFilterError());
	}

	public function testOffsetUnsetErrorsDoesNotAffectHtmlOrDom()
	{
		// Clearing errors must leave the HTML string and DOM resource untouched.
		$p = new TRenderFilterParameterParseFail('<p>keep</p>');
		$p->getFilterDOM(); // returns false; HTML stays current
		unset($p[TRenderFilterParameter::RENDER_FILTER_ERRORS]);
		$this->assertSame('<p>keep</p>', $p->getFilterText());
	}

	public function testOffsetExistsErrorsAfterUnset()
	{
		$p = new TRenderFilterParameterParseFail('<p>bad</p>');
		$p->getFilterDOM();
		unset($p[TRenderFilterParameter::RENDER_FILTER_ERRORS]);
		$this->assertFalse(isset($p[TRenderFilterParameter::RENDER_FILTER_ERRORS]));
	}
}
