<?php

/**
 * TRenderFilterParameter class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

use DOMDocument;
use DOMComment;
use DOMDocumentType;
use DOMElement;
use DOMNode;
use DOMXPath;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IEventCycleParameter;
use Prado\Prado;
use Prado\TEventParameter;
use Prado\TPropertyValue;

/**
 * TRenderFilterParameter class
 *
 * Event parameter for the `onRenderFilter` event raised by {@see TControl::renderControl}.
 * It carries the rendered HTML and exposes two representations that can be switched
 * between transparently:
 *
 * - **HTML string** — the raw rendered markup, accessed via {@see getFilterText} /
 *   {@see setFilterText} or via array-access key `'html'`
 *   ({@see RENDER_FILTER_TEXT}).
 * - **DOMDocument** — a parsed DOM tree, accessed via {@see getFilterDOM} /
 *   {@see setFilterDOM} or via array-access key `'dom'`
 *   ({@see RENDER_FILTER_DOM}).
 *
 * ## Resource selection
 *
 * The parameter automatically tracks which representation is *current* (authoritative):
 * - Calling {@see getFilterDOM} (or reading `$param['dom']`) parses the current HTML
 *   into a `DOMDocument` and makes DOM the current resource.
 * - Calling {@see getFilterText} (or reading `$param['html']`) while DOM is current
 *   serialises the DOM back to HTML and makes the string the current resource.
 * - Setting either representation via setter or array-access makes it current and
 *   invalidates the other.
 *
 * ## postRaiseEvent
 *
 * Implements {@see \Prado\IEventCycleParameter} via its parent
 * {@see \Prado\TEventParameter}. If DOM is the current resource when
 * {@see postRaiseEvent} is called, the DOM is automatically serialized to HTML so
 * that {@see TControl::processRenderFilter} always receives a valid string from {@see getFilterText}.
 *
 * ## Parsing
 *
 * The HTML parses in the context of the element that holds its first tag, so
 * table rows, cells, and options parse as elements, leading text gets no `<p>`, and
 * whitespace between top-level elements stays. A document or a `<head>` or
 * `<body>` fragment parses as a document.
 *
 * | PHP | Parser |
 * |---|---|
 * | 8.4+ | The HTML5 parser (`Dom\HTMLDocument`); the nodes import into the `DOMDocument`. Attribute names `DOMDocument` rejects, such as `@click`, are kept. |
 * | 8.2, 8.3 | libxml's HTML parser. `<script>` and `<style>` content, and `<svg>` and `<math>` elements, are placeholders in the DOM and return unchanged. Attribute names libxml drops, such as `@click`, are kept. |
 *
 * Both serialize with libxml. Entities decode to characters, and a self-closing
 * SVG element gains an end tag.
 *
 * ## Exempt markup
 *
 * An element with {@see EXEMPT_ATTRIBUTE} (`data-prado-filter-exempt`) and its
 * content are exempt from the handlers. Before the handlers run,
 * {@see preRaiseEvent} replaces the exempt markup with placeholder comments;
 * {@see postRaiseEvent} restores it. The nearest attribute of an element or its
 * ancestors decides:
 *
 * | Nearest attribute | Handlers |
 * |---|---|
 * | none | see the element |
 * | present, empty, or any value but `false` | see a placeholder |
 * | `false` | see the element, inside an exempt ancestor |
 *
 * A re-enabled element stays in place among the placeholders, which also hold the
 * start and end tags of its exempt ancestors. The attribute stays in the output,
 * so each enclosing filter exempts the same markup. HTML without the attribute
 * does not parse. {@see TControl::getRenderFilterExempt} exempts a control's output.
 *
 * ## Depth limit
 *
 * The handlers of a control with {@see TControl::getRenderFilterDepth} see the
 * output of the controls at most {@see getDepthLimit} levels below the control.
 * The output of a deeper control is a placeholder comment; {@see postRaiseEvent}
 * restores it. A handler sets its own limit from 1 to the RenderFilterDepth, or 0
 * to see all output; the limit applies to the handlers that follow. The output
 * holds marker comments around the output of the controls within the limit.
 *
 * ## DOM element walker
 *
 * {@see walkElements} performs a depth-first traversal of every `DOMElement` in the
 * document, passing each element and this parameter to a callback. A specific subtree
 * root may be supplied to confine the walk.
 *
 * ## Array-access compatibility
 *
 * The three reserved keys are proxied through the getter/setter methods so that
 * event handlers using the array-access style work correctly:
 * ```php
 * $control->onRenderFilter[] = function ($sender, $param) {
 *     // Array-access style
 *     $html = $param[TRenderFilterParameter::RENDER_FILTER_TEXT];
 *     $param[TRenderFilterParameter::RENDER_FILTER_TEXT] = strtoupper($html);
 *
 *     // DOM API — add missing alt attributes to all images
 *     $param->walkElements(function (\DOMElement $el, $p) {
 *         if ($el->tagName === 'img' && !$el->hasAttribute('alt')) {
 *             $el->setAttribute('alt', '');
 *         }
 *     });
 * };
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 * @see TFilterRenderableTrait
 * @see TControl::renderControl
 * @see IEventCycleParameter
 */
class TRenderFilterParameter extends TEventParameter implements IEventCycleParameter
{
	/**
	 * Array-access key for the HTML string representation.
	 */
	public const RENDER_FILTER_TEXT = 'html';

	/**
	 * Array-access key for the DOMDocument representation.
	 */
	public const RENDER_FILTER_DOM = 'dom';

	/**
	 * Array-access key for the libxml parse error list.
	 * The stored value is `null` when no errors have been captured, or a `LibXMLError[]`
	 * array after a parse that produced at least one error.
	 */
	public const RENDER_FILTER_ERRORS = 'errors';

	/**
	 * The attribute that exempts an element and its content from the handlers.
	 * Present, empty, or any value other than `false` exempts; `false` re-enables
	 * the element inside an exempt ancestor.
	 */
	public const EXEMPT_ATTRIBUTE = 'data-prado-filter-exempt';

	/**
	 * The HTML namespace of HTML5 parser elements.
	 */
	private const HTML_NAMESPACE = 'http://www.w3.org/1999/xhtml';

	/**
	 * The HTML5 parser document class of PHP 8.4+. It is named by string so the
	 * class resolves only when it exists.
	 */
	private const HTML5_DOCUMENT_CLASS = 'Dom\HTMLDocument';

	/**
	 * One attribute of a start tag: its leading separators, name, and `=` value.
	 */
	private const ATTRIBUTE = '/([\s\/]*+)([^\s\/>][^\s\/>=]*+)((?>\s*=\s*(?>"[^"]*"|\'[^\']*\'|[^\s>]*+))?)/';

	/**
	 * The attributes of a start tag after its element name, through its `>`. Group 1
	 * holds a name libxml's HTML parser drops when the tag has one.
	 */
	private const START_TAG_ATTRIBUTES = '/\G(?>[\s\/]*+(?>' . self::LIBXML_NAME . '(?=[\s\/>=])|([^\s\/>][^\s\/>=]*+))(?>\s*=\s*(?>"[^"]*"|\'[^\']*\'|[^\s>]*+))?)*+[\s\/]*+>/i';

	/**
	 * An attribute name libxml's HTML parser keeps: an ASCII XML name.
	 */
	private const LIBXML_NAME = '[a-z_:][a-z0-9_:.\-]*+';

	/**
	 * @var bool true when the DOM has been modified and the HTML string has not yet been re-synced
	 */
	private bool $_domCurrent = false;

	/**
	 * @var array<string, string> the markup and names the parse replaced, by placeholder
	 */
	private array $_rawMarkup = [];

	/**
	 * @var string the random part of the placeholders of the current parse
	 */
	private string $_nonce = '';

	/**
	 * @var array<string, string> the exempt markup, by placeholder
	 */
	private array $_exemptMarkup = [];

	/**
	 * @var string the random part of the exempt markup placeholders
	 */
	private string $_exemptNonce = '';

	/**
	 * @var bool whether the HTML is part of the output of the filtering control
	 */
	private bool $_isFragment = false;

	/**
	 * @var string the comment data that starts the markers of the control levels
	 */
	private string $_depthMarker = '';

	/**
	 * @var int the RenderFilterDepth of the filtering control
	 */
	private int $_depthMax = 0;

	/**
	 * @var int the levels below the filtering control that the handlers see; 0 for all
	 */
	private int $_depthLimit = 0;

	/**
	 * @var array<string, string> the output of the deeper controls, by placeholder
	 */
	private array $_depthHidden = [];

	/**
	 * Constructor.
	 * @param string $html the rendered HTML to filter
	 */
	public function __construct(string $html = '')
	{
		parent::__construct([
			self::RENDER_FILTER_TEXT => $html,
			self::RENDER_FILTER_DOM => null,
			self::RENDER_FILTER_ERRORS => null,
		]);
	}

	/**
	 * Returns whether the HTML is part of the output of the filtering control: the
	 * output of a descendant that re-renders alone during a callback
	 * ({@see TControl::getRenderFilterFragments}). The HTML holds the start tags
	 * that enclose the descendant, marker comments around its output, and the end
	 * tags. A handler that depends on the whole output, such as one counting
	 * elements, checks it.
	 * @return bool whether the HTML is a fragment of the control's output
	 * @since 4.4.0
	 */
	public function getIsFragment(): bool
	{
		return $this->_isFragment;
	}

	/**
	 * @param bool $value whether the HTML is a fragment of the control's output
	 * @since 4.4.0
	 */
	public function setIsFragment(bool $value): void
	{
		$this->_isFragment = $value;
	}

	/**
	 * Returns the levels of controls below the filtering control whose output the
	 * handlers see. The output of a deeper control is a placeholder comment.
	 * @return int the depth limit; 0 for all output
	 * @since 4.4.0
	 */
	public function getDepthLimit(): int
	{
		return $this->_depthLimit;
	}

	/**
	 * Sets the levels of controls whose output the following handlers see, and
	 * discards the DOM.
	 * @param int $value the depth limit, from 1 to the RenderFilterDepth of the
	 *   filtering control; 0 for all output
	 * @throws TInvalidDataValueException if the value is negative or greater than the RenderFilterDepth
	 * @since 4.4.0
	 */
	public function setDepthLimit($value): void
	{
		$value = TPropertyValue::ensureInteger($value);
		if ($value < 0 || $value > $this->_depthMax) {
			throw new TInvalidDataValueException('renderfilterparameter_depthlimit_invalid', $value, $this->_depthMax);
		}
		if ($value === $this->_depthLimit) {
			return;
		}
		$this->_depthLimit = $value;
		$html = $this->restoreDepth($this->getFilterText());
		if ($value > 0) {
			$marker = preg_quote($this->_depthMarker . ($value + 1) . '-->', '/');
			$html = preg_replace_callback('/<!--' . $marker . '.*?<!--\/' . $marker . '/s', fn ($match) => $this->depthToken($match[0]), $html);
		}
		$this->setFilterText($html);
	}

	/**
	 * Sets the markers of the control levels in the HTML, and hides the output
	 * deeper than the limit. {@see TRenderFilterContext::createDepthParameter} calls it.
	 * @param string $marker the comment data that starts the markers of the control levels
	 * @param int $limit the RenderFilterDepth of the filtering control
	 * @since 4.4.0
	 */
	public function setDepthMarker(string $marker, int $limit): void
	{
		$this->_depthMarker = $marker;
		$this->_depthMax = max(0, $limit);
		$this->setDepthLimit($this->_depthMax);
	}

	/**
	 * @param string $html the HTML
	 * @return string the HTML with the output of the deeper controls restored
	 */
	private function restoreDepth(string $html): string
	{
		while ($this->_depthHidden !== [] && ($restored = strtr($html, $this->_depthHidden)) !== $html) {
			$html = $restored;
		}
		return $html;
	}

	/**
	 * @param string $markup the output of a deeper control
	 * @return string the placeholder comment of the output
	 */
	private function depthToken(string $markup): string
	{
		$token = '<!--' . $this->_depthMarker . 'h' . count($this->_depthHidden) . '-->';
		$this->_depthHidden[$token] = $markup;
		return $token;
	}

	// -------------------------------------------------------------------------
	// Sync helpers
	// -------------------------------------------------------------------------

	/**
	 * Serializes the DOM to HTML and makes the string current.  No-op when the string is already current.
	 */
	protected function ensureFilterText(): void
	{
		if ($this->_domCurrent) {
			$dom = parent::offsetGet(self::RENDER_FILTER_DOM);
			if ($dom instanceof DOMDocument) {
				parent::offsetSet(self::RENDER_FILTER_TEXT, $this->domToHtml($dom));
			}
			$this->_domCurrent = false;
		}
	}

	/**
	 * Parses the HTML string into the DOM slot if it is `null`.  No-op otherwise.
	 *
	 * Stores `false` when {@see htmlToDom} reports a fatal parse failure.
	 * Does **not** set `_domCurrent` — callers must do so.  The flag is separate
	 * because it must be re-asserted on every {@see getFilterDOM} call, not only
	 * on first parse (after {@see ensureFilterText} clears it, the DOM may already
	 * be populated but `_domCurrent` is `false`).
	 */
	protected function ensureFilterDOM(): void
	{
		if (parent::offsetGet(self::RENDER_FILTER_DOM) === null) {
			$html = parent::offsetGet(self::RENDER_FILTER_TEXT) ?? '';
			parent::offsetSet(self::RENDER_FILTER_DOM, $this->htmlToDom($html));
		}
	}

	// -------------------------------------------------------------------------
	// HTML string accessor
	// -------------------------------------------------------------------------

	/**
	 * Returns the current HTML string, serialising from DOM first if DOM is current.
	 *
	 * @return string rendered HTML
	 */
	public function getFilterText(): string
	{
		$this->ensureFilterText();
		return parent::offsetGet(self::RENDER_FILTER_TEXT) ?? '';
	}

	/**
	 * Replaces the HTML string and makes the string resource current.
	 * Discards any cached DOM and parse errors.
	 *
	 * @param string $html new HTML string
	 */
	public function setFilterText(string $html): void
	{
		parent::offsetSet(self::RENDER_FILTER_TEXT, $html);
		parent::offsetSet(self::RENDER_FILTER_DOM, null);
		$this->_domCurrent = false;
		$this->storeErrors(null);
	}

	// -------------------------------------------------------------------------
	// DOM accessor
	// -------------------------------------------------------------------------

	/**
	 * Returns the parsed `DOMDocument`, making DOM the current resource.
	 *
	 * Parsed lazily from the HTML string on first call or after {@see setFilterText}.
	 * The same instance is returned on repeated calls.  No `<html>`, `<head>`, or
	 * `<body>` wrappers are added.  Returns `false` when a fatal libxml parse failure
	 * occurs; in that case the HTML string remains current and unmodified.
	 *
	 * @return DOMDocument|false parsed document, or `false` on fatal parse failure
	 */
	public function getFilterDOM(): DOMDocument|false
	{
		$this->ensureFilterDOM();
		$dom = parent::offsetGet(self::RENDER_FILTER_DOM);
		if ($dom instanceof DOMDocument) {
			$this->_domCurrent = true;
			return $dom;
		}
		return false;
	}

	/**
	 * Replaces the DOM and makes DOM the current resource.
	 * Clears any stored parse errors.
	 *
	 * @param DOMDocument $dom the new document
	 */
	public function setFilterDOM(DOMDocument $dom): void
	{
		parent::offsetSet(self::RENDER_FILTER_DOM, $dom);
		$this->_domCurrent = true;
		$this->storeErrors(null);
	}

	// -------------------------------------------------------------------------
	// Parse-error accessors
	// -------------------------------------------------------------------------

	/**
	 * Returns libxml errors captured during the most recent {@see htmlToDom} call,
	 * or `null` when no parse has been attempted, the last parse produced no errors, or
	 * errors were cleared via {@see setFilterText} / {@see setFilterDOM} /
	 * `unset($param[TRenderFilterParameter::RENDER_FILTER_ERRORS])`.
	 *
	 * @return ?\LibXMLError[]
	 */
	public function getFilterErrors(): ?array
	{
		return parent::offsetGet(self::RENDER_FILTER_ERRORS);
	}

	/**
	 * Returns `true` when the most recent parse captured at least one libxml error.
	 *
	 * @return bool
	 */
	public function getHasFilterError(): bool
	{
		return parent::offsetGet(self::RENDER_FILTER_ERRORS) !== null;
	}

	// -------------------------------------------------------------------------
	// DOM walker
	// -------------------------------------------------------------------------

	/**
	 * Depth-first traversal of every `DOMElement` in the document (or a subtree).
	 *
	 * Calls `$callback(\DOMElement $element, TRenderFilterParameter $param, int $depth)`
	 * for each element.  `$depth` is `0` for direct children of `$node`, `1` for their
	 * children, and so on.  The visit list is snapshotted before the first callback fires,
	 * so DOM mutations during the walk do not affect which elements are visited.
	 * Pass a `DOMNode` as `$node` to confine the walk to a subtree; pass `false` for
	 * `$recursive` to visit only direct element children (depth 0).
	 * Makes DOM the current resource via {@see getFilterDOM}.
	 *
	 * ```php
	 * $param->walkElements(function (\DOMElement $el, $p) {
	 *     if ($el->tagName === 'img' && !$el->hasAttribute('alt')) {
	 *         $el->setAttribute('alt', '');
	 *     }
	 * });
	 * ```
	 *
	 * @param callable $callback called as `(\DOMElement, TRenderFilterParameter, int $depth): void`
	 * @param ?DOMNode $node starting node; `null` walks the whole document
	 * @param bool $recursive descend into children (default `true`)
	 */
	public function walkElements(callable $callback, ?DOMNode $node = null, bool $recursive = true): void
	{
		if ($node === null) {
			$dom = $this->getFilterDOM();
			if ($dom === false) {
				return;
			}
			$node = $dom;
		}
		$list = [];
		$this->collectElements($node, $recursive, 0, $list);
		foreach ($list as [$element, $depth]) {
			$callback($element, $this, $depth);
		}
	}

	/**
	 * Recursively collects `DOMElement` children of `$node` depth-first into `$list`.
	 * Each entry is `[$element, $depth]`.
	 *
	 * @param DOMNode $node node whose element children to collect
	 * @param bool $recursive descend into each collected element
	 * @param int $depth depth relative to the original starting node
	 * @param array $list collected entries, passed by reference
	 */
	private function collectElements(DOMNode $node, bool $recursive, int $depth, array &$list): void
	{
		foreach ($node->childNodes as $child) {
			if ($child instanceof DOMElement) {
				$list[] = [$child, $depth];
				if ($recursive) {
					$this->collectElements($child, true, $depth + 1, $list);
				}
			}
		}
	}

	// -------------------------------------------------------------------------
	// IEventCycleParameter — lifecycle hooks
	// -------------------------------------------------------------------------

	/**
	 * Replaces the markup exempt from the handlers with placeholder comments before
	 * the handlers run ({@see protectExemptMarkup}).
	 *
	 * @param string $name event name
	 * @param mixed $sender event sender
	 * @param \Prado\TEventParameter $param this parameter instance
	 * @param ?int $responsetype response accumulation mode
	 * @param ?callable $postfunction per-handler post-processing function
	 */
	public function preRaiseEvent($name, $sender, $param, $responsetype, $postfunction): void
	{
		$this->protectExemptMarkup();
	}

	/**
	 * Serialises DOM→HTML after all handlers run (if DOM is current), so
	 * {@see TControl::processRenderFilter} always receives a valid string, and
	 * restores the exempt markup and the output of the deeper controls.
	 *
	 * @param array $responses aggregated handler responses
	 * @param string $name event name
	 * @param mixed $sender event sender
	 * @param \Prado\TEventParameter $param this parameter instance
	 * @param ?int $responsetype response accumulation mode
	 * @param ?callable $postfunction per-handler post-processing function
	 */
	public function postRaiseEvent($responses, $name, $sender, $param, $responsetype, $postfunction): void
	{
		$this->ensureFilterText();
		if ($this->_exemptMarkup !== [] || $this->_depthHidden !== []) {
			$html = parent::offsetGet(self::RENDER_FILTER_TEXT) ?? '';
			$hidden = $this->_exemptMarkup + $this->_depthHidden;
			while (($restored = strtr($html, $hidden)) !== $html) {
				$html = $restored;
			}
			parent::offsetSet(self::RENDER_FILTER_TEXT, $html);
			parent::offsetSet(self::RENDER_FILTER_DOM, null);
			$this->_exemptMarkup = [];
			$this->_depthHidden = [];
		}
		$this->_depthLimit = 0;
	}

	// -------------------------------------------------------------------------
	// Exempt markup
	// -------------------------------------------------------------------------

	/**
	 * Replaces each element exempt from the handlers ({@see EXEMPT_ATTRIBUTE}), with
	 * its content, by a placeholder comment that {@see postRaiseEvent} restores.
	 *
	 * The nearest {@see EXEMPT_ATTRIBUTE} of an element or its ancestors decides
	 * whether the handlers see it. A re-enabled element (`false`) inside an exempt
	 * element stays in place, and placeholders replace the exempt markup around it,
	 * including the start and end tags of its exempt ancestors.
	 *
	 * HTML without the attribute does not parse. The exempt markup serializes from
	 * the DOM, so it is equivalent to the rendered HTML.
	 */
	protected function protectExemptMarkup(): void
	{
		if (stripos($this->getFilterText(), self::EXEMPT_ATTRIBUTE) === false || !($dom = $this->getFilterDOM())) {
			return;
		}
		$this->_exemptNonce = bin2hex(random_bytes(4));
		$xpath = new DOMXPath($dom);
		foreach (iterator_to_array($xpath->query('//*[@' . self::EXEMPT_ATTRIBUTE . ']')) as $element) {
			if ($this->getIsExemptRoot($element)) {
				$this->protectExemptElement($element, $xpath);
			}
		}
	}

	/**
	 * @param DOMElement $element an element with {@see EXEMPT_ATTRIBUTE}
	 * @return bool whether its attribute value exempts it
	 */
	private function getIsExemptValue(DOMElement $element): bool
	{
		return strcasecmp(trim($element->getAttribute(self::EXEMPT_ATTRIBUTE)), 'false') !== 0;
	}

	/**
	 * @param DOMElement $element an element
	 * @return ?DOMElement the nearest ancestor with {@see EXEMPT_ATTRIBUTE}, or null
	 */
	private function getExemptAncestor(DOMElement $element): ?DOMElement
	{
		for ($node = $element->parentNode; $node instanceof DOMElement; $node = $node->parentNode) {
			if ($node->hasAttribute(self::EXEMPT_ATTRIBUTE)) {
				return $node;
			}
		}
		return null;
	}

	/**
	 * An element inside a placeholder keeps its exempt ancestor, so it is not a root.
	 * @param DOMElement $element an element with {@see EXEMPT_ATTRIBUTE}
	 * @return bool whether the element is exempt and inside no exempt element
	 */
	private function getIsExemptRoot(DOMElement $element): bool
	{
		if (!$this->getIsExemptValue($element)) {
			return false;
		}
		$ancestor = $this->getExemptAncestor($element);
		return $ancestor === null || !$this->getIsExemptValue($ancestor);
	}

	/**
	 * Replaces an exempt element by placeholder comments and the re-enabled elements inside it.
	 *
	 * @param DOMElement $element the exempt element inside no exempt element
	 * @param DOMXPath $xpath the XPath of the document
	 */
	private function protectExemptElement(DOMElement $element, DOMXPath $xpath): void
	{
		$enabled = [];
		$ancestors = [];
		foreach ($xpath->query('.//*[@' . self::EXEMPT_ATTRIBUTE . ']', $element) as $descendant) {
			if ($this->getIsExemptValue($descendant)) {
				continue;
			}
			if (!$this->getIsExemptValue($this->getExemptAncestor($descendant))) {
				continue;
			}
			$enabled[spl_object_id($descendant)] = $descendant;
			for ($node = $descendant->parentNode; !$node->isSameNode($element); $node = $node->parentNode) {
				$ancestors[spl_object_id($node)] = $node;
			}
		}
		$nodes = [];
		$markup = '';
		if ($enabled !== []) {
			$ancestors[spl_object_id($element)] = $element;
		}
		$this->collectExemptNodes($element, $enabled, $ancestors, $nodes, $markup);
		if ($markup !== '') {
			$nodes[] = $this->exemptComment($markup);
		}
		$parent = $element->parentNode;
		foreach ($nodes as $node) {
			$parent->insertBefore($node, $element);
		}
		$parent->removeChild($element);
	}

	/**
	 * Collects the nodes that replace an exempt node: placeholder comments for the
	 * exempt markup and the re-enabled elements in place.
	 *
	 * @param DOMNode $node the exempt node
	 * @param array<int, DOMElement> $enabled the re-enabled elements, by object id
	 * @param array<int, DOMNode> $ancestors the exempt ancestors of the re-enabled elements below the exempt element, by object id
	 * @param DOMNode[] $nodes the collected nodes, passed by reference
	 * @param string $markup the exempt markup not yet in a placeholder, passed by reference
	 */
	private function collectExemptNodes(DOMNode $node, array $enabled, array $ancestors, array &$nodes, string &$markup): void
	{
		$id = spl_object_id($node);
		if (isset($enabled[$id])) {
			if ($markup !== '') {
				$nodes[] = $this->exemptComment($markup);
				$markup = '';
			}
			$nodes[] = $node;
			return;
		}
		if (!isset($ancestors[$id])) {
			$markup .= $this->getExemptHtml($node);
			return;
		}
		$html = $this->getExemptHtml($node->cloneNode(false));
		$endTag = strtr('</' . $node->nodeName . '>', $this->_rawMarkup);
		$markup .= str_ends_with($html, $endTag) ? substr($html, 0, -strlen($endTag)) : $html;
		foreach (iterator_to_array($node->childNodes) as $child) {
			$this->collectExemptNodes($child, $enabled, $ancestors, $nodes, $markup);
		}
		$markup .= $endTag;
	}

	/**
	 * @param DOMNode $node a node of the document
	 * @return string the HTML of the node, with the parse placeholders restored
	 */
	private function getExemptHtml(DOMNode $node): string
	{
		return strtr($node->ownerDocument->saveHTML($node), $this->_rawMarkup);
	}

	/**
	 * Stores exempt markup for {@see postRaiseEvent} to restore.
	 *
	 * @param string $markup the exempt markup
	 * @return DOMComment the placeholder comment
	 */
	private function exemptComment(string $markup): DOMComment
	{
		$data = TRenderFilterContext::EXEMPT_TOKEN . $this->_exemptNonce . '-' . count($this->_exemptMarkup);
		$this->_exemptMarkup['<!--' . $data . '-->'] = $markup;
		return $this->getFilterDOM()->createComment($data);
	}

	// -------------------------------------------------------------------------
	// ArrayAccess overrides — proxy reserved keys through getters/setters
	// -------------------------------------------------------------------------

	/**
	 * `isset($param['html'])` always returns true.
	 * `isset($param['dom'])` always returns true (DOM can always be created from HTML).
	 * `isset($param['errors'])` returns true when errors are stored (i.e. `getFilterErrors() !== null`).
	 * {@inheritdoc}
	 */
	public function offsetExists($offset): bool
	{
		if ($offset === self::RENDER_FILTER_TEXT || $offset === self::RENDER_FILTER_DOM) {
			return true;
		}
		if ($offset === self::RENDER_FILTER_ERRORS) {
			return $this->getHasFilterError();
		}
		return parent::offsetExists($offset);
	}

	/**
	 * `$param['html']` proxies to {@see getFilterText}.
	 * `$param['dom']` proxies to {@see getFilterDOM}.
	 * `$param['errors']` proxies to {@see getFilterErrors} (`null` when no errors).
	 * {@inheritdoc}
	 */
	public function offsetGet($offset): mixed
	{
		if ($offset === self::RENDER_FILTER_TEXT) {
			return $this->getFilterText();
		}
		if ($offset === self::RENDER_FILTER_DOM) {
			return $this->getFilterDOM();
		}
		if ($offset === self::RENDER_FILTER_ERRORS) {
			return $this->getFilterErrors();
		}
		return parent::offsetGet($offset);
	}

	/**
	 * `$param['html'] = $v` proxies to {@see setFilterText}.
	 * `$param['dom'] = $v` proxies to {@see setFilterDOM} (value must be a `DOMDocument`).
	 * `$param['errors'] = $v` is a no-op; errors are set by the parse process.
	 * {@inheritdoc}
	 */
	public function offsetSet($offset, $item): void
	{
		if ($offset === self::RENDER_FILTER_TEXT) {
			$this->setFilterText((string) $item);
			return;
		}
		if ($offset === self::RENDER_FILTER_DOM) {
			if ($item instanceof DOMDocument) {
				$this->setFilterDOM($item);
			}
			return;
		}
		if ($offset === self::RENDER_FILTER_ERRORS) {
			return; // read-only; populated by htmlToDom via storeErrors
		}
		parent::offsetSet($offset, $item);
	}

	/**
	 * `unset($param['html'])` clears the HTML string to `''` and discards any cached DOM.
	 * `unset($param['dom'])` commits any pending DOM changes to the HTML string first
	 * (so no modifications are lost), then discards the DOM object.  The next call
	 * to `$param['dom']` will re-parse from the (now-current) HTML.
	 * `unset($param['errors'])` clears the captured parse error list.
	 * {@inheritdoc}
	 */
	public function offsetUnset($offset): void
	{
		if ($offset === self::RENDER_FILTER_TEXT) {
			$this->setFilterText('');
			return;
		}
		if ($offset === self::RENDER_FILTER_DOM) {
			$this->ensureFilterText(); // commits DOM→HTML if DOM is the current resource
			parent::offsetSet(self::RENDER_FILTER_DOM, null);
			return;
		}
		if ($offset === self::RENDER_FILTER_ERRORS) {
			$this->storeErrors(null);
			return;
		}
		parent::offsetUnset($offset);
	}

	// -------------------------------------------------------------------------
	// Protected helpers
	// -------------------------------------------------------------------------

	/**
	 * Stores `$errors` in the parameter's array slot for {@see RENDER_FILTER_ERRORS},
	 * bypassing the no-op in {@see offsetSet}.  `null` or an empty array is stored as
	 * `null` (the no-errors sentinel).  Called by {@see htmlToDom}; subclasses that
	 * override `htmlToDom` should call this to keep errors consistent.
	 *
	 * @param ?\LibXMLError[] $errors errors to store, or `null` to clear
	 */
	protected function storeErrors(?array $errors): void
	{
		parent::offsetSet(self::RENDER_FILTER_ERRORS, !empty($errors) ? $errors : null);
	}

	// -------------------------------------------------------------------------
	// Parsing and serialization
	// -------------------------------------------------------------------------

	/**
	 * Parses `$html` into a `DOMDocument` without wrapper elements.
	 *
	 * The fragment parses in the context of the element that holds its first tag
	 * ({@see getFragmentContext}), so the parse adds no `<p>`, keeps the whitespace
	 * between top-level elements, and keeps table rows, cells, and options. The
	 * HTML5 parser of PHP 8.4+ (`Dom\HTMLDocument`) parses when
	 * {@see getUseHtml5Parser} is true, and the nodes import into the `DOMDocument`;
	 * otherwise libxml's HTML parser parses. All parse errors are retained via
	 * {@see storeErrors}. Returns `false` when libxml reports a fatal failure; the
	 * error details are also logged via {@see Prado::warning}.
	 *
	 * @param string $html raw HTML fragment or document
	 * @return DOMDocument|false parsed document, or `false` on fatal parse failure
	 */
	protected function htmlToDom(string $html): DOMDocument|false
	{
		$dom = new DOMDocument('1.0', 'UTF-8');
		$this->_rawMarkup = [];
		$this->_nonce = bin2hex(random_bytes(4));
		if (trim($html) === '') {
			return $dom;
		}
		if ($this->getUseHtml5Parser()) {
			return $this->parseHtml5($html, $dom);
		}
		return $this->parseLibxml($html, $dom);
	}

	/**
	 * @return bool whether the HTML5 parser of PHP 8.4+ (`Dom\HTMLDocument`) parses the HTML
	 */
	protected function getUseHtml5Parser(): bool
	{
		return class_exists(self::HTML5_DOCUMENT_CLASS);
	}

	/**
	 * Returns the element whose content model fits the first tag of `$html`.
	 *
	 * | First tag | Context |
	 * |---|---|
	 * | `<!DOCTYPE>`, `<html>` | `#document` (a whole document) |
	 * | `<head>`, `<body>` | `html` |
	 * | `<tr>` | `tbody` |
	 * | `<td>`, `<th>` | `tr` |
	 * | `<caption>`, `<colgroup>`, `<thead>`, `<tbody>`, `<tfoot>` | `table` |
	 * | `<col>` | `colgroup` |
	 * | `<option>`, `<optgroup>` | `select` |
	 * | any other, or text | `body` |
	 *
	 * @param string $html raw HTML fragment or document
	 * @return string the context element name, or `#document`
	 */
	protected function getFragmentContext(string $html): string
	{
		if (!preg_match('/^(?:\s|<!--.*?-->)*<(!doctype|[a-z][a-z0-9-]*)/is', $html, $match)) {
			return 'body';
		}
		return match (strtolower($match[1])) {
			'!doctype', 'html' => '#document',
			'head', 'body' => 'html',
			'tr' => 'tbody',
			'td', 'th' => 'tr',
			'caption', 'colgroup', 'thead', 'tbody', 'tfoot' => 'table',
			'col' => 'colgroup',
			'option', 'optgroup' => 'select',
			default => 'body',
		};
	}

	/**
	 * Parses `$html` with libxml's HTML parser into `$dom`.
	 *
	 * The fragment parses inside its context element, which is then removed.
	 * libxml's HTML4 parser ends `<script>` and `<style>` content at the first
	 * `</`, and lowercases SVG and MathML names. In the DOM, a placeholder
	 * holds the content of `<script>` and `<style>` and replaces each `<svg>` and
	 * `<math>` element, and a placeholder name replaces each attribute name
	 * libxml drops ({@see protectRawMarkup}); serialization restores them.
	 * Handlers do not change that content.
	 *
	 * @param string $html raw HTML fragment or document
	 * @param DOMDocument $dom the document to parse into
	 * @return DOMDocument|false parsed document, or `false` on fatal parse failure
	 */
	private function parseLibxml(string $html, DOMDocument $dom): DOMDocument|false
	{
		$context = $this->getFragmentContext($html);
		$html = $this->protectRawMarkup($html);
		if ($context !== '#document') {
			$html = '<' . $context . '>' . $html . '</' . $context . '>';
		}
		$saved = libxml_use_internal_errors(true);
		$result = $dom->loadHTML(
			'<?xml encoding="UTF-8">' . $html,
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		$errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors($saved);
		$this->storeErrors($errors);
		if ($result === false) {
			$details = implode('; ', array_values(array_filter(
				array_map(fn ($e) => trim($e->message), $errors)
			)));
			Prado::warning(
				'Failed to parse HTML fragment into DOM' . ($details !== '' ? ': ' . $details : '.'),
				static::class
			);
			return false;
		}
		$prefix = $dom->firstChild;
		// libxml can place the doctype before the injected encoding prefix.
		if ($prefix instanceof DOMDocumentType) {
			$prefix = $prefix->nextSibling;
		}
		if ($prefix !== null && (
			($prefix->nodeType === XML_PI_NODE && $prefix->nodeName === 'xml')
			|| ($prefix->nodeType === XML_COMMENT_NODE
				&& $prefix->nodeValue === '?xml encoding="UTF-8"')
		)) {
			$dom->removeChild($prefix);
		}
		if ($context !== '#document' && ($wrapper = $dom->documentElement) instanceof DOMElement) {
			while ($wrapper->firstChild) {
				$dom->insertBefore($wrapper->firstChild, $wrapper);
			}
			$dom->removeChild($wrapper);
		}
		return $dom;
	}

	/**
	 * Replaces `<script>` and `<style>` content, and `<svg>` and `<math>` elements,
	 * with placeholder comments, and the attribute names libxml drops with
	 * placeholder names ({@see protectAttributeNames}). {@see domToHtml} restores both.
	 * Comments pass unchanged.
	 *
	 * @param string $html raw HTML
	 * @return string the HTML with placeholders
	 */
	private function protectRawMarkup(string $html): string
	{
		$result = '';
		$copied = 0;
		$offset = 0;
		while (preg_match('/<!--|<([a-z][^\s\/>]*)/i', $html, $match, PREG_OFFSET_CAPTURE, $offset)) {
			$start = $match[0][1];
			if ($match[0][0] === '<!--') {
				$end = strpos($html, '-->', $start + 4);
				$offset = ($end === false) ? strlen($html) : $end + 3;
				continue;
			}
			$tag = strtolower($match[1][0]);
			if ($tag === 'svg' || $tag === 'math') {
				$end = $this->findElementEnd($html, $tag, $start);
				$result .= substr($html, $copied, $start - $copied) . $this->rawMarkupToken(substr($html, $start, $end - $start));
				$offset = $copied = $end;
				continue;
			}
			$nameEnd = $start + strlen($match[0][0]);
			if (!preg_match(self::START_TAG_ATTRIBUTES, $html, $attributes, 0, $nameEnd)) {
				break;
			}
			$offset = $nameEnd + strlen($attributes[0]);
			if (isset($attributes[1])) {
				$result .= substr($html, $copied, $nameEnd - $copied) . $this->protectAttributeNames($attributes[0]);
				$copied = $offset;
			}
			if ($tag === 'script' || $tag === 'style') {
				$close = stripos($html, '</' . $tag, $offset);
				$close = ($close === false) ? strlen($html) : $close;
				$result .= substr($html, $copied, $offset - $copied) . $this->rawMarkupToken(substr($html, $offset, $close - $offset));
				$offset = $copied = $close;
			}
		}
		return $result . substr($html, $copied);
	}

	/**
	 * Replaces each attribute name of a start tag that libxml's HTML parser drops
	 * with a placeholder name ({@see nameToken}). libxml keeps ASCII XML names only,
	 * so names such as `@click` and `ü` are replaced.
	 *
	 * @param string $attributes the start tag after its element name, through its `>`
	 * @return string the attributes with placeholder names
	 */
	private function protectAttributeNames(string $attributes): string
	{
		return preg_replace_callback(
			self::ATTRIBUTE,
			fn ($m) => $m[1] . (preg_match('/^' . self::LIBXML_NAME . '$/i', $m[2]) ? $m[2] : $this->nameToken($m[2])) . $m[3],
			$attributes
		);
	}

	/**
	 * Returns the offset after the end tag that closes the `$tag` element at `$start`,
	 * counting nested elements of the same name.
	 *
	 * @param string $html raw HTML
	 * @param string $tag the element name
	 * @param int $start offset of the element's start tag
	 * @return int offset after the end tag, or the length of `$html` when unclosed
	 */
	private function findElementEnd(string $html, string $tag, int $start): int
	{
		$depth = 0;
		$offset = $start;
		while (preg_match('/<(\/?)' . $tag . '(?=[\s\/>])[^>]*>/i', $html, $match, PREG_OFFSET_CAPTURE, $offset)) {
			$offset = $match[0][1] + strlen($match[0][0]);
			if ($match[1][0] === '/') {
				if (--$depth <= 0) {
					return $offset;
				}
			} elseif (!str_ends_with($match[0][0], '/>')) {
				$depth++;
			} elseif ($depth === 0) {
				return $offset;
			}
		}
		return strlen($html);
	}

	/**
	 * Stores `$markup` for {@see domToHtml} to restore.
	 *
	 * @param string $markup the markup to keep
	 * @return string the placeholder comment, or '' for empty markup
	 */
	private function rawMarkupToken(string $markup): string
	{
		if ($markup === '') {
			return '';
		}
		$token = '<!--prado-raw-' . $this->_nonce . '-' . count($this->_rawMarkup) . '-->';
		$this->_rawMarkup[$token] = $markup;
		return $token;
	}

	/**
	 * Parses `$html` with the HTML5 parser of PHP 8.4+ and imports the nodes into `$dom`.
	 *
	 * A fragment parses as the content of its context element. A document, or a
	 * fragment starting with `<head>` or `<body>`, parses as a document; the
	 * `<html>`, `<head>`, and `<body>` elements that `$html` has no start tag for
	 * are left out and their content imports in their place.
	 *
	 * @param string $html raw HTML fragment or document
	 * @param DOMDocument $dom the document to import into
	 * @return DOMDocument the parsed document
	 */
	private function parseHtml5(string $html, DOMDocument $dom): DOMDocument
	{
		$context = $this->getFragmentContext($html);
		$class = self::HTML5_DOCUMENT_CLASS;
		$saved = libxml_use_internal_errors(true);
		if ($context === '#document' || $context === 'html') {
			$source = $class::createFromString($html, 0, 'UTF-8');
			if ($context === '#document' && ($doctype = $source->doctype) !== null) {
				$dom->appendChild($dom->implementation->createDocumentType($doctype->name, $doctype->publicId, $doctype->systemId));
			}
			$parent = $source;
		} else {
			$source = $class::createEmpty();
			$parent = $source->createElement($context);
			$source->appendChild($parent);
			$parent->innerHTML = $html;
		}
		$errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors($saved);
		$this->storeErrors($errors);
		$this->importHtml5Children($parent, $dom, $dom, $html);
		return $dom;
	}

	/**
	 * Imports the children of an HTML5 node as children of `$target`.
	 *
	 * A `<template>` holds its content outside its child nodes; the content
	 * parses again from the template's `innerHTML`.
	 *
	 * @param object $source the parent `Dom\Node` of the HTML5 document
	 * @param DOMNode $target the node receiving the imported children
	 * @param DOMDocument $dom the document owning the imported nodes
	 * @param string $html the parsed HTML, to find the elements with a start tag
	 */
	private function importHtml5Children(object $source, DOMNode $target, DOMDocument $dom, string $html): void
	{
		if ($source->nodeType === XML_ELEMENT_NODE && $source->localName === 'template' && $source->namespaceURI === self::HTML_NAMESPACE) {
			$class = self::HTML5_DOCUMENT_CLASS;
			$content = $source->innerHTML;
			$document = $class::createEmpty();
			$source = $document->createElement($this->getFragmentContext($content));
			$document->appendChild($source);
			$source->innerHTML = $content;
			$html = $content;
		}
		foreach ($source->childNodes as $child) {
			if ($child->nodeType === XML_ELEMENT_NODE) {
				if ($child->namespaceURI === self::HTML_NAMESPACE && in_array($child->localName, ['html', 'head', 'body'], true)
						&& !preg_match('/<' . $child->localName . '[\s\/>]/i', $html)) {
					$this->importHtml5Children($child, $target, $dom, $html);
					continue;
				}
				$element = $dom->createElement($this->getXmlName($child->localName));
				foreach ($child->attributes as $attribute) {
					$element->setAttribute($this->getXmlName($attribute->name), $attribute->value);
				}
				$this->importHtml5Children($child, $element, $dom, $html);
				$target->appendChild($element);
			} elseif ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
				$target->appendChild($dom->createTextNode($child->data));
			} elseif ($child->nodeType === XML_COMMENT_NODE) {
				$target->appendChild($dom->createComment($child->data));
			}
		}
	}

	/**
	 * Returns `$name` when it is a valid XML name; otherwise a placeholder name that
	 * {@see domToHtml} restores. HTML allows names such as `@click` and `[prop]`
	 * that `DOMDocument` rejects.
	 *
	 * @param string $name an element or attribute name
	 * @return string the name, or its placeholder
	 */
	private function getXmlName(string $name): string
	{
		if (preg_match('/^[\p{L}_:][\p{L}\p{N}_:.\-]*$/u', $name)) {
			return $name;
		}
		return $this->nameToken($name);
	}

	/**
	 * Stores `$name` for {@see domToHtml} to restore.
	 *
	 * @param string $name an element or attribute name
	 * @return string the placeholder name
	 */
	private function nameToken(string $name): string
	{
		$alias = 'prado-name-' . $this->_nonce . '-' . count($this->_rawMarkup);
		$this->_rawMarkup[$alias] = $name;
		return $alias;
	}

	/**
	 * Serializes all child nodes of `$dom` to an HTML string, restoring the markup
	 * and names the parse replaced with placeholders.
	 *
	 * @param DOMDocument $dom
	 * @return string
	 */
	private function domToHtml(DOMDocument $dom): string
	{
		$html = '';
		foreach ($dom->childNodes as $child) {
			if ($child instanceof DOMDocumentType) {
				$html .= '<!DOCTYPE ' . $child->name
					. ($child->publicId !== '' ? ' PUBLIC "' . $child->publicId . '"' : '')
					. ($child->systemId !== '' ? ($child->publicId !== '' ? '' : ' SYSTEM') . ' "' . $child->systemId . '"' : '')
					. '>';
			} else {
				$html .= $dom->saveHTML($child);
			}
		}
		return $this->_rawMarkup ? strtr($html, $this->_rawMarkup) : $html;
	}
}
