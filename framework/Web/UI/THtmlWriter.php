<?php
/**
 * THtmlWriter class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

use Prado\Web\THttpUtility;

/**
 * THtmlWriter class
 *
 * THtmlWriter is a writer that renders valid XHTML outputs.
 * It provides functions to render tags, their attributes and stylesheet fields.
 * Attribute and stylesheet values will be automatically HTML-encoded if
 * they require so. For example, the 'value' attribute in an input tag
 * will be encoded.
 *
 * A common usage of THtmlWriter is as the following sequence:
 * <code>
 *  $writer->addAttribute($name1,$value1);
 *  $writer->addAttribute($name2,$value2);
 *  $writer->renderBeginTag($tagName);
 *  // ... render contents enclosed within the tag here
 *  $writer->renderEndTag();
 * </code>
 * Make sure each invocation of {@link renderBeginTag} is accompanied with
 * a {@link renderEndTag} and they are properly nested, like nesting
 * tags in HTML and XHTML.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI
 * @since 3.0
 */
class THtmlWriter extends \Prado\TApplicationComponent implements \Prado\IO\ITextWriter
{
	/**
	 * @var array list of tags are do not need a closing tag
	 */
	private static $_simpleTags = [
		'area' => true,
		'base' => true,
		'basefont' => true,
		'bgsound' => true,
		'col' => true,
		'embed' => true,
		'frame' => true,
		'hr' => true,
		'img' => true,
		'input' => true,
		'isindex' => true,
		'link' => true,
		'meta' => true,
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
	 * Constructor.
	 * @param ITextWriter $writer a writer that THtmlWriter will pass its rendering result to
	 */
	public function __construct($writer)
	{
		$this->_writer = $writer;
	}

	public function getWriter()
	{
		return $this->_writer;
	}

	public function setWriter($writer)
	{
		$this->_writer = $writer;
	}
	/**
	 * Adds a list of attributes to be rendered.
	 * @param array $attrs list of attributes to be rendered
	 */
	public function addAttributes($attrs)
	{
		foreach ($attrs as $name => $value) {
			$this->_attributes[THttpUtility::htmlStrip($name)] = THttpUtility::htmlEncode($value);
		}
	}

	/**
	 * Adds an attribute to be rendered.
	 * @param string $name name of the attribute
	 * @param string $value value of the attribute
	 */
	public function addAttribute($name, $value)
	{
		$this->_attributes[THttpUtility::htmlStrip($name)] = THttpUtility::htmlEncode($value);
	}

	/**
	 * Removes the named attribute from rendering
	 * @param string $name name of the attribute to be removed
	 */
	public function removeAttribute($name)
	{
		unset($this->_attributes[THttpUtility::htmlStrip($name)]);
	}

	/**
	 * Adds a list of stylesheet attributes to be rendered.
	 * @param array $attrs list of stylesheet attributes to be rendered
	 */
	public function addStyleAttributes($attrs)
	{
		foreach ($attrs as $name => $value) {
			$this->_styles[THttpUtility::htmlStrip($name)] = THttpUtility::htmlEncode($value);
		}
	}

	/**
	 * Adds a stylesheet attribute to be rendered
	 * @param string $name stylesheet attribute name
	 * @param string $value stylesheet attribute value
	 */
	public function addStyleAttribute($name, $value)
	{
		$this->_styles[THttpUtility::htmlStrip($name)] = THttpUtility::htmlEncode($value);
	}

	/**
	 * Removes the named stylesheet attribute from rendering
	 * @param string $name name of the stylesheet attribute to be removed
	 */
	public function removeStyleAttribute($name)
	{
		unset($this->_styles[THttpUtility::htmlStrip($name)]);
	}

	/**
	 * Flushes the rendering result.
	 * This will invoke the underlying writer's flush method.
	 * @return string the content being flushed
	 */
	public function flush()
	{
		return $this->_writer->flush();
	}

	/**
	 * Renders a string.
	 * @param string $str string to be rendered
	 */
	public function write($str)
	{
		$this->_writer->write($str);
	}

	/**
	 * Renders a string and appends a newline to it.
	 * @param string $str string to be rendered
	 */
	public function writeLine($str = '')
	{
		$this->_writer->write($str . "\n");
	}

	/**
	 * Renders an HTML break.
	 */
	public function writeBreak()
	{
		$this->_writer->write('<br/>');
	}

	/**
	 * Renders the openning tag.
	 * @param string $tagName tag name
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
		if (isset(self::$_simpleTags[$tagName])) {
			$str .= ' />';
			$this->_openTags[] = '';
		} else {
			$str .= '>';
			$this->_openTags[] = $tagName;
		}
		$this->_writer->write($str);
		$this->_attributes = [];
		$this->_styles = [];
	}

	/**
	 * Renders the closing tag.
	 */
	public function renderEndTag()
	{
		if (!empty($this->_openTags) && ($tagName = array_pop($this->_openTags)) !== '') {
			$this->_writer->write('</' . $tagName . '>');
		}
	}
}
