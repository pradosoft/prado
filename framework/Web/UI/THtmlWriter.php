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
use Prado\Web\THttpUtility;

/**
 * THtmlWriter class
 *
 * THtmlWriter is a writer that renders valid XHTML and HTML5 outputs.
 * It provides functions to render tags, their attributes and stylesheet fields.
 * Attribute and stylesheet values will be automatically HTML-encoded if
 * they require so. For example, the 'value' attribute in an input tag
 * will be encoded.
 *
 * This writer supports the void elements defined in the HTML5 spec. When
 * a void element such as `<br>`, `<img>`, or `<input>` is rendered, it
 * will be closed with a self‑closing tag (`/>`). Legacy PRADO void
 * elements (e.g., `basefont`, `bgsound`, `frame`, `isindex`) are still
 * supported for backward compatibility but are marked deprecated and
 * may be removed in future releases.
 *
 * A common usage of THtmlWriter is as the following sequence:
 * ```php
 *  $writer->addAttribute($name1,$value1);
 *  $writer->addAttribute($name2,$value2);
 *  $writer->renderBeginTag($tagName);
 *  // ... render contents enclosed within the tag here
 *  $writer->renderEndTag();
 * ```
 * Make sure each invocation of {@see renderBeginTag} is accompanied with
 * a {@see renderEndTag} and they are properly nested, like nesting
 * tags in HTML and XHTML.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class THtmlWriter extends \Prado\TApplicationComponent implements \Prado\IO\ITextWriter
{
	/**
	 * @todo v4.4 basefont, bgsound, frame, isindex are legacy. remove them. Apparently, not used in PRADO.
	 * @var array list of tags that are void elements (no closing tag needed)
	 * @see https://html.spec.whatwg.org/multipage/syntax.html#void-elements
	 */
	private static $_voidElements = [
		'area' => true,
		'base' => true,
		'basefont' => true,	// --
		'bgsound' => true,	// --
		'br' => true,		// ++
		'col' => true,
		'embed' => true,
		'frame' => true,	// --
		'hr' => true,
		'img' => true,
		'input' => true,
		'isindex' => true,	// --
		'link' => true,
		'meta' => true,
		'source' => true,	// ++
		'track' => true,	// ++
		'wbr' => true,
	];
	/**
	 * @var array list of attributes to be rendered for a tag
	 */
	private $_attributes = [];
	/**
	 * @var array list of openning tags
	 */
	private $_openTags = [];
	/**
	 * @var array list of style attributes
	 */
	private $_styles = [];
	/**
	 * @var ITextWriter writer
	 */
	protected $_writer;

	/**
	 * basefont, bgsound, frame, isindex are legacy PRADO void Elements.
	 * They are deprecated, and will be removed in a future version.
	 * @return array List of void element tag names
	 * @since 4.3.3
	 */
	public static function getVoidElements(): array
	{
		return array_keys(self::$_voidElements);
	}

	/**
	 * basefont, bgsound, frame, isindex are legacy PRADO void Elements.
	 * They are deprecated, and will be removed in a future version.
	 * @param string $tag Name of the tag to check
	 * @return bool Whether the tag is a void element
	 * @since 4.3.3
	 */
	public static function isVoidElement(string $tag): bool
	{
		return isset(self::$_voidElements[strtolower($tag)]);
	}

	/**
	 * Constructor.
	 * @param ITextWriter $writer A writer that THtmlWriter will pass its rendering result to
	 */
	public function __construct($writer)
	{
		$this->setWriter($writer);
		parent::__construct();
	}

	/**
	 * Returns the underlying writer.
	 * @return ITextWriter The writer instance used by this THtmlWriter
	 */
	public function getWriter()
	{
		return $this->_writer;
	}

	/**
	 * Sets the underlying writer.
	 * @param ITextWriter $writer The writer instance used by this THtmlWriter
	 */
	public function setWriter($writer)
	{
		$this->_writer = $writer;
	}

	/**
	 * Adds a list of attributes to be rendered.
	 * @param array $attrs List of attributes to be rendered
	 */
	public function addAttributes($attrs)
	{
		foreach ($attrs as $name => $value) {
			$this->_attributes[THttpUtility::htmlStrip($name)] = THttpUtility::htmlEncode($value);
		}
	}

	/**
	 * Adds an attribute to be rendered.
	 * @param string $name Name of the attribute
	 * @param string $value Value of the attribute
	 */
	public function addAttribute($name, $value)
	{
		$this->_attributes[THttpUtility::htmlStrip($name)] = THttpUtility::htmlEncode($value);
	}

	/**
	 * Removes the named attribute from rendering
	 * @param string $name Name of the attribute to be removed
	 */
	public function removeAttribute($name)
	{
		unset($this->_attributes[THttpUtility::htmlStrip($name)]);
	}

	/**
	 * Adds a list of stylesheet attributes to be rendered.
	 * @param array $attrs List of stylesheet attributes to be rendered
	 */
	public function addStyleAttributes($attrs)
	{
		foreach ($attrs as $name => $value) {
			$this->_styles[THttpUtility::htmlStrip($name)] = THttpUtility::htmlEncode($value);
		}
	}

	/**
	 * Adds a stylesheet attribute to be rendered
	 * @param string $name Stylesheet attribute name
	 * @param string $value Stylesheet attribute value
	 */
	public function addStyleAttribute($name, $value)
	{
		$this->_styles[THttpUtility::htmlStrip($name)] = THttpUtility::htmlEncode($value);
	}

	/**
	 * Removes the named stylesheet attribute from rendering
	 * @param string $name Name of the stylesheet attribute to be removed
	 */
	public function removeStyleAttribute($name)
	{
		unset($this->_styles[THttpUtility::htmlStrip($name)]);
	}

	/**
	 * Flushes the rendering result.
	 * This will invoke the underlying writer's flush method.
	 * @return string Content being flushed
	 */
	public function flush()
	{
		return $this->getWriter()->flush();
	}

	/**
	 * Renders a string.
	 * @param string $str String to be rendered
	 */
	public function write($str)
	{
		$this->getWriter()->write($str);
	}

	/**
	 * Renders a string and appends a newline to it.
	 * @param string $str String to be rendered
	 */
	public function writeLine($str = '')
	{
		$this->write($str . "\n");
	}

	/**
	 * Renders an HTML break.
	 */
	public function writeBreak()
	{
		$this->write('<br/>');
	}

	/**
	 * Renders the openning tag.
	 * @param string $tagName Tag name
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
	 * Renders the closing tag.
	 */
	public function renderEndTag()
	{
		if (!empty($this->_openTags) && ($tagName = array_pop($this->_openTags)) !== '') {
			$this->write('</' . $tagName . '>');
		}
	}
}
