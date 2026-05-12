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
use DOMElement;
use DOMNode;
use Prado\IEventCycleParameter;
use Prado\Prado;
use Prado\TEventParameter;

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
	 * @var bool true when the DOM has been modified and the HTML string has not yet been re-synced
	 */
	private bool $_domCurrent = false;

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
		$this->storeErrors([]);
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
		$this->storeErrors([]);
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
	 * @return null|\LibXMLError[]
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
	 * @param null|DOMNode $node starting node; `null` walks the whole document
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
	 * Serialises DOM→HTML after all handlers run (if DOM is current), so
	 * {@see TControl::processRenderFilter} always receives a valid string.
	 *
	 * @param array $responses aggregated handler responses
	 * @param string $name event name
	 * @param mixed $sender event sender
	 * @param \Prado\TEventParameter $param this parameter instance
	 * @param null|int $responsetype response accumulation mode
	 * @param null|callable $postfunction per-handler post-processing function
	 */
	public function postRaiseEvent($responses, $name, $sender, $param, $responsetype, $postfunction): void
	{
		$this->ensureFilterText();
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
			$this->storeErrors([]);
			return;
		}
		parent::offsetUnset($offset);
	}

	// -------------------------------------------------------------------------
	// Protected helpers
	// -------------------------------------------------------------------------

	/**
	 * Stores `$errors` in the parameter's array slot for {@see RENDER_FILTER_ERRORS},
	 * bypassing the no-op in {@see offsetSet}.  An empty array is stored as `null`
	 * (the no-errors sentinel).  Called by {@see htmlToDom}; subclasses that override
	 * `htmlToDom` should call this to keep errors consistent.
	 *
	 * @param \LibXMLError[] $errors
	 */
	protected function storeErrors(array $errors): void
	{
		parent::offsetSet(self::RENDER_FILTER_ERRORS, empty($errors) ? null : $errors);
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Parses `$html` into a `DOMDocument` without wrapper elements.
	 *
	 * Uses `LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD`.  All libxml errors are
	 * retained via {@see storeErrors} regardless of parse success.  Returns `false`
	 * when `loadHTML` reports a fatal failure; the error details are also logged via
	 * {@see Prado::warning}.
	 *
	 * @param string $html raw HTML fragment
	 * @return DOMDocument|false parsed document, or `false` on fatal parse failure
	 */
	protected function htmlToDom(string $html): DOMDocument|false
	{
		$dom = new DOMDocument('1.0', 'UTF-8');
		if (trim($html) !== '') {
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
			foreach (iterator_to_array($dom->childNodes) as $node) {
				if ($node->nodeType === XML_PI_NODE && $node->nodeName === 'xml') {
					$dom->removeChild($node);
				}
			}
		}
		return $dom;
	}

	/**
	 * Serialises all child nodes of `$dom` to an HTML string.
	 *
	 * @param DOMDocument $dom
	 * @return string
	 */
	private function domToHtml(DOMDocument $dom): string
	{
		$html = '';
		foreach ($dom->childNodes as $child) {
			$html .= $dom->saveHTML($child);
		}
		return $html;
	}
}
