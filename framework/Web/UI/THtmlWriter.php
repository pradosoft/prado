<?php

/**
 * THtmlWriter class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

use Prado\IO\ITextWriter;
use Prado\IO\TTextWriter;
use Prado\Web\THttpUtility;

/**
 * THtmlWriter class
 *
 * THtmlWriter renders valid XHTML and HTML5 output. It provides methods to
 * render tags, their attributes, and inline style declarations. Attribute and
 * style values are automatically HTML-encoded via {@see THttpUtility::htmlEncode()}
 * (translates `<`, `>`, and `"` to their entities; `&` is left untouched).
 *
 * Void elements defined in the HTML5 spec (`area`, `base`, `br`, `col`, `embed`,
 * `hr`, `img`, `input`, `link`, `meta`, `source`, `track`, `wbr`) are self-closed
 * with ` />` syntax.
 *
 * Typical rendering sequence:
 * ```php
 *  $writer->addAttribute($name1, $value1);
 *  $writer->addAttribute($name2, $value2);
 *  $writer->renderBeginTag($tagName);
 *  // ... render content enclosed within the tag
 *  $writer->renderEndTag();
 * ```
 * Each {@see renderBeginTag} call must be paired with a {@see renderEndTag}
 * call, properly nested like HTML.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class THtmlWriter extends \Prado\TApplicationComponent implements \Prado\IO\ITextWriter
{
	/**
	 * @var array map of void element tag names (lowercase) to `true`
	 * @see https://html.spec.whatwg.org/multipage/syntax.html#void-elements
	 */
	private static $_voidElements = [
		'area' => true,
		'base' => true,
		'br' => true,
		'col' => true,
		'embed' => true,
		'hr' => true,
		'img' => true,
		'input' => true,
		'link' => true,
		'meta' => true,
		'source' => true,
		'track' => true,
		'wbr' => true,
	];
	/**
	 * @var array pending attributes to render on the next {@see renderBeginTag} call
	 */
	private $_attributes = [];
	/**
	 * @var array stack of open tag names; void elements push an empty string as a sentinel
	 */
	private $_openTags = [];
	/**
	 * @var array pending style declarations to render on the next {@see renderBeginTag} call
	 */
	private $_styles = [];
	/**
	 * @var ?ITextWriter underlying writer receiving all output
	 */
	protected $_writer;

	/**
	 * Returns all void element tag names as a flat list.
	 * @return array list of void element tag names
	 * @since 4.3.3
	 */
	public static function getVoidElements(): array
	{
		return array_keys(self::$_voidElements);
	}

	/**
	 * Returns whether `$tag` is a void element (case-insensitive).
	 * @param string $tag tag name to test
	 * @return bool true when `$tag` is a void element
	 * @since 4.3.3
	 */
	public static function isVoidElement(string $tag): bool
	{
		return isset(self::$_voidElements[strtolower($tag)]);
	}

	/**
	 * Constructor.
	 *
	 * - `true` (default) — creates a fresh {@see TTextWriter} as the underlying writer.
	 * - `null` — no writer is set; caller must call {@see setWriter()} before rendering.
	 * - `ITextWriter` instance — uses the provided writer directly.
	 *
	 * @param null|ITextWriter|true $writer underlying writer, `true` to auto-create a {@see TTextWriter}
	 */
	public function __construct($writer = true)
	{
		if ($writer === true) {
			$writer = new TTextWriter();
		}
		if ($writer !== null) {
			$this->setWriter($writer);
		}
		parent::__construct();
	}

	/**
	 * Returns the underlying writer.
	 * @return ?ITextWriter the writer instance used by this THtmlWriter
	 */
	public function getWriter()
	{
		return $this->_writer;
	}

	/**
	 * Sets the underlying writer.
	 * @param ?ITextWriter $writer writer instance to use
	 * @return $this for method chaining
	 */
	public function setWriter($writer)
	{
		$this->_writer = $writer;
		return $this;
	}

	/**
	 * Queues a batch of attributes for the next {@see renderBeginTag} call.
	 * Keys and values are processed identically to {@see addAttribute}.
	 *
	 * @param array $attrs map of attribute name → value pairs
	 * @return $this for method chaining
	 */
	public function addAttributes($attrs)
	{
		foreach ($attrs as $name => $value) {
			$this->_attributes[THttpUtility::htmlStrip($name)] = THttpUtility::htmlEncode($value);
		}
		return $this;
	}

	/**
	 * Queues a single attribute for the next {@see renderBeginTag} call.
	 * The name is passed through {@see THttpUtility::htmlStrip()} and the value
	 * through {@see THttpUtility::htmlEncode()}. An existing attribute with the
	 * same name is overwritten.
	 *
	 * @param string $name attribute name
	 * @param string $value attribute value
	 * @return $this for method chaining
	 */
	public function addAttribute($name, $value)
	{
		$this->_attributes[THttpUtility::htmlStrip($name)] = THttpUtility::htmlEncode($value);
		return $this;
	}

	/**
	 * Removes a queued attribute by name.
	 * The name is normalized via {@see THttpUtility::htmlStrip()} before lookup,
	 * so the name must match exactly how it was added. A no-op when the attribute
	 * is not in the queue.
	 *
	 * @param string $name attribute name to remove
	 * @return $this for method chaining
	 */
	public function removeAttribute($name)
	{
		unset($this->_attributes[THttpUtility::htmlStrip($name)]);
		return $this;
	}

	/**
	 * Queues a batch of style declarations for the next {@see renderBeginTag} call.
	 * Keys and values are processed identically to {@see addStyleAttribute}.
	 *
	 * @param array $attrs map of CSS property name → value pairs
	 * @return $this for method chaining
	 */
	public function addStyleAttributes($attrs)
	{
		foreach ($attrs as $name => $value) {
			$this->_styles[THttpUtility::htmlStrip($name)] = THttpUtility::htmlEncode($value);
		}
		return $this;
	}

	/**
	 * Queues a single style declaration for the next {@see renderBeginTag} call.
	 * The property name is passed through {@see THttpUtility::htmlStrip()} and the
	 * value through {@see THttpUtility::htmlEncode()}. An existing declaration with
	 * the same property name is overwritten.
	 *
	 * @param string $name CSS property name (e.g. `'color'`, `'font-size'`)
	 * @param string $value CSS property value
	 * @return $this for method chaining
	 */
	public function addStyleAttribute($name, $value)
	{
		$this->_styles[THttpUtility::htmlStrip($name)] = THttpUtility::htmlEncode($value);
		return $this;
	}

	/**
	 * Removes a queued style declaration by property name.
	 * The name is normalized via {@see THttpUtility::htmlStrip()} before lookup.
	 * A no-op when the property is not in the queue.
	 *
	 * @param string $name CSS property name to remove
	 * @return $this for method chaining
	 */
	public function removeStyleAttribute($name)
	{
		unset($this->_styles[THttpUtility::htmlStrip($name)]);
		return $this;
	}

	/**
	 * Delegates to the underlying writer's `flush()` and returns the accumulated output.
	 * @return string content that was flushed
	 */
	public function flush()
	{
		return $this->getWriter()->flush();
	}

	/**
	 * Passes `$str` to the underlying writer without modification.
	 * @param string $str string to write
	 * @return $this for method chaining
	 */
	public function write($str)
	{
		$this->getWriter()->write($str);
		return $this;
	}

	/**
	 * Writes `$str` followed by a Unix newline (`\n`).
	 * @param string $str string to write; defaults to an empty string (writes a bare newline)
	 * @return $this for method chaining
	 */
	public function writeLine($str = '')
	{
		$this->write($str . "\n");
		return $this;
	}

	/**
	 * Writes a self-closing `<br/>` tag.
	 * @return $this for method chaining
	 */
	public function writeBreak()
	{
		$this->write('<br/>');
		return $this;
	}

	/**
	 * Renders the opening tag for `$tagName`, including all queued attributes and
	 * style declarations, then clears both queues.
	 *
	 * - **Non-void element** (e.g. `div`, `span`): writes `<tagName attrs style>` and
	 *   pushes `$tagName` onto the open-tag stack.
	 * - **Void element** (e.g. `br`, `img`, `input`): writes `<tagName attrs style />`
	 *   and pushes an empty-string sentinel so that a matching {@see renderEndTag} call
	 *   is a no-op rather than writing a spurious closing tag.
	 *
	 * @param string $tagName HTML tag name; case preserved in output, void check is case-insensitive
	 */
	public function renderBeginTag($tagName)
	{
		$str = '<' . $tagName;
		foreach ($this->_attributes as $name => $value) {
			$str .= ' ' . $name . '="' . $value . '"';
		}
		if (!empty($this->_styles)) {
			$str .= ' style="';
			foreach ($this->_styles as $name => $value) {
				$str .= $name . ':' . $value . ';';
			}
			$str .= '"';
		}
		if (static::isVoidElement($tagName)) {
			$str .= ' />';
			$this->_openTags[] = '';
		} else {
			$str .= '>';
			$this->_openTags[] = $tagName;
		}
		$this->write($str);
		$this->_attributes = [];
		$this->_styles = [];
	}

	/**
	 * Renders the closing tag for the most recently opened non-void element.
	 *
	 * Pops the top entry from the open-tag stack. When the entry is a non-empty
	 * string (a normal element), writes `</tagName>`. When the entry is an empty
	 * string (the void-element sentinel pushed by {@see renderBeginTag}), nothing
	 * is written. When the stack is empty, nothing happens.
	 */
	public function renderEndTag()
	{
		if (!empty($this->_openTags) && ($tagName = array_pop($this->_openTags)) !== '') {
			$this->write('</' . $tagName . '>');
		}
	}
}
