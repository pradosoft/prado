<?php
/**
 * TXmlElement, TXmlDocument, TXmlElementList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Xml
 */

namespace Prado\Xml;

use \Prado\TPropertyValue;
use \Prado\Collections\TList;
use \Prado\Collections\TMap;

/**
 * TXmlElement class.
 *
 * TXmlElement represents an XML element node.
 * You can obtain its tag-name, attributes, text between the opening and closing
 * tags via the TagName, Attributes, and Value properties, respectively.
 * You can also retrieve its parent and child elements by Parent and Elements
 * properties, respectively.
 *
 * TBD: xpath
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Xml
 * @since 3.0
 */
class TXmlElement extends \Prado\TComponent
{
	/**
	 * @var TXmlElement parent of this element
	 */
	private $_parent;
	/**
	 * @var string tag-name of this element
	 */
	private $_tagName = 'unknown';
	/**
	 * @var string text enclosed between opening and closing tags of this element
	 */
	private $_value = '';
	/**
	 * @var TXmlElementList list of child elements of this element
	 */
	private $_elements;
	/**
	 * @var TMap attributes of this element
	 */
	private $_attributes;

	/**
	 * Constructor.
	 * @param string $tagName tag-name for this element
	 */
	public function __construct($tagName)
	{
		$this->setTagName($tagName);
	}

	/**
	 * @return TXmlElement parent element of this element
	 */
	public function getParent()
	{
		return $this->_parent;
	}

	/**
	 * @param TXmlElement $parent parent element of this element
	 */
	public function setParent($parent)
	{
		$this->_parent = $parent;
	}

	/**
	 * @return string tag-name of this element
	 */
	public function getTagName()
	{
		return $this->_tagName;
	}

	/**
	 * @param string $tagName tag-name of this element
	 */
	public function setTagName($tagName)
	{
		$this->_tagName = $tagName;
	}

	/**
	 * @return string text enclosed between opening and closing tag of this element
	 */
	public function getValue()
	{
		return $this->_value;
	}

	/**
	 * @param string $value text enclosed between opening and closing tag of this element
	 */
	public function setValue($value)
	{
		$this->_value = TPropertyValue::ensureString($value);
	}

	/**
	 * @return bool true if this element has child elements
	 */
	public function getHasElement()
	{
		return $this->_elements !== null && $this->_elements->getCount() > 0;
	}

	/**
	 * @return bool true if this element has attributes
	 */
	public function getHasAttribute()
	{
		return $this->_attributes !== null && $this->_attributes->getCount() > 0;
	}

	/**
	 * @param string $name attribute name
	 * @return string the attribute specified by the name, null if no such attribute
	 */
	public function getAttribute($name)
	{
		if ($this->_attributes !== null) {
			return $this->_attributes->itemAt($name);
		} else {
			return null;
		}
	}

	/**
	 * @param string $name attribute name
	 * @param string $value attribute value
	 */
	public function setAttribute($name, $value)
	{
		$this->getAttributes()->add($name, TPropertyValue::ensureString($value));
	}

	/**
	 * @return TXmlElementList list of child elements
	 */
	public function getElements()
	{
		if (!$this->_elements) {
			$this->_elements = new TXmlElementList($this);
		}
		return $this->_elements;
	}

	/**
	 * @return TMap list of attributes
	 */
	public function getAttributes()
	{
		if (!$this->_attributes) {
			$this->_attributes = new TMap;
		}
		return $this->_attributes;
	}

	/**
	 * @param mixed $tagName
	 * @return TXmlElement the first child element that has the specified tag-name, null if not found
	 */
	public function getElementByTagName($tagName)
	{
		if ($this->_elements) {
			foreach ($this->_elements as $element) {
				if ($element->_tagName === $tagName) {
					return $element;
				}
			}
		}
		return null;
	}

	/**
	 * @param mixed $tagName
	 * @return TList list of all child elements that have the specified tag-name
	 */
	public function getElementsByTagName($tagName)
	{
		$list = new TList;
		if ($this->_elements) {
			foreach ($this->_elements as $element) {
				if ($element->_tagName === $tagName) {
					$list->add($element);
				}
			}
		}
		return $list;
	}

	/**
	 * @param mixed $indent
	 * @return string string representation of this element
	 */
	public function toString($indent = 0)
	{
		$attr = '';
		if ($this->_attributes !== null) {
			foreach ($this->_attributes as $name => $value) {
				$value = $this->xmlEncode($value);
				$attr .= " $name=\"$value\"";
			}
		}
		$prefix = str_repeat(' ', $indent * 4);
		if ($this->getHasElement()) {
			$str = $prefix . "<{$this->_tagName}$attr>\n";
			foreach ($this->getElements() as $element) {
				$str .= $element->toString($indent + 1) . "\n";
			}
			$str .= $prefix . "</{$this->_tagName}>";
			return $str;
		} elseif (($value = $this->getValue()) !== '') {
			$value = $this->xmlEncode($value);
			return $prefix . "<{$this->_tagName}$attr>$value</{$this->_tagName}>";
		} else {
			return $prefix . "<{$this->_tagName}$attr />";
		}
	}

	/**
	 * Magic-method override. Called whenever this element is used as a string.
	 * <code>
	 * $element = new TXmlElement('tag');
	 * echo $element;
	 * </code>
	 * or
	 * <code>
	 * $element = new TXmlElement('tag');
	 * $xml = (string)$element;
	 * </code>
	 * @return string string representation of this element
	 */
	public function __toString()
	{
		return $this->toString();
	}

	private function xmlEncode($str)
	{
		return strtr($str, [
			'>' => '&gt;',
			'<' => '&lt;',
			'&' => '&amp;',
			'"' => '&quot;',
			"\r" => '&#xD;',
			"\t" => '&#x9;',
			"\n" => '&#xA;']);
	}
}
