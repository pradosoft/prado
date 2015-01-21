<?php
/**
 * TXmlElement, TXmlDocument, TXmlElementList class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package Prado\Xml
 */

namespace Prado\Xml;

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
class TXmlElement extends TComponent
{
	/**
	 * @var TXmlElement parent of this element
	 */
	private $_parent=null;
	/**
	 * @var string tag-name of this element
	 */
	private $_tagName='unknown';
	/**
	 * @var string text enclosed between opening and closing tags of this element
	 */
	private $_value='';
	/**
	 * @var TXmlElementList list of child elements of this element
	 */
	private $_elements=null;
	/**
	 * @var TMap attributes of this element
	 */
	private $_attributes=null;

	/**
	 * Constructor.
	 * @param string tag-name for this element
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
	 * @param TXmlElement parent element of this element
	 */
	public function setParent($parent)
	{
		$this->_parent=$parent;
	}

	/**
	 * @return string tag-name of this element
	 */
	public function getTagName()
	{
		return $this->_tagName;
	}

	/**
	 * @param string tag-name of this element
	 */
	public function setTagName($tagName)
	{
		$this->_tagName=$tagName;
	}

	/**
	 * @return string text enclosed between opening and closing tag of this element
	 */
	public function getValue()
	{
		return $this->_value;
	}

	/**
	 * @param string text enclosed between opening and closing tag of this element
	 */
	public function setValue($value)
	{
		$this->_value=TPropertyValue::ensureString($value);
	}

	/**
	 * @return boolean true if this element has child elements
	 */
	public function getHasElement()
	{
		return $this->_elements!==null && $this->_elements->getCount()>0;
	}

	/**
	 * @return boolean true if this element has attributes
	 */
	public function getHasAttribute()
	{
		return $this->_attributes!==null && $this->_attributes->getCount()>0;
	}

	/**
	 * @return string the attribute specified by the name, null if no such attribute
	 */
	public function getAttribute($name)
	{
		if($this->_attributes!==null)
			return $this->_attributes->itemAt($name);
		else
			return null;
	}

	/**
	 * @param string attribute name
	 * @param string attribute value
	 */
	public function setAttribute($name,$value)
	{
		$this->getAttributes()->add($name,TPropertyValue::ensureString($value));
	}

	/**
	 * @return TXmlElementList list of child elements
	 */
	public function getElements()
	{
		if(!$this->_elements)
			$this->_elements=new TXmlElementList($this);
		return $this->_elements;
	}

	/**
	 * @return TMap list of attributes
	 */
	public function getAttributes()
	{
		if(!$this->_attributes)
			$this->_attributes=new TMap;
		return $this->_attributes;
	}

	/**
	 * @return TXmlElement the first child element that has the specified tag-name, null if not found
	 */
	public function getElementByTagName($tagName)
	{
		if($this->_elements)
		{
			foreach($this->_elements as $element)
				if($element->_tagName===$tagName)
					return $element;
		}
		return null;
	}

	/**
	 * @return TList list of all child elements that have the specified tag-name
	 */
	public function getElementsByTagName($tagName)
	{
		$list=new TList;
		if($this->_elements)
		{
			foreach($this->_elements as $element)
				if($element->_tagName===$tagName)
					$list->add($element);
		}
		return $list;
	}

	/**
	 * @return string string representation of this element
	 */
	public function toString($indent=0)
	{
		$attr='';
		if($this->_attributes!==null)
		{
			foreach($this->_attributes as $name=>$value)
			{
				$value=$this->xmlEncode($value);
				$attr.=" $name=\"$value\"";
			}
		}
		$prefix=str_repeat(' ',$indent*4);
		if($this->getHasElement())
		{
			$str=$prefix."<{$this->_tagName}$attr>\n";
			foreach($this->getElements() as $element)
				$str.=$element->toString($indent+1)."\n";
			$str.=$prefix."</{$this->_tagName}>";
			return $str;
		}
		else if(($value=$this->getValue())!=='')
		{
			$value=$this->xmlEncode($value);
			return $prefix."<{$this->_tagName}$attr>$value</{$this->_tagName}>";
		}
		else
			return $prefix."<{$this->_tagName}$attr />";
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
		return strtr($str,array(
			'>'=>'&gt;',
			'<'=>'&lt;',
			'&'=>'&amp;',
			'"'=>'&quot;',
			"\r"=>'&#xD;',
			"\t"=>'&#x9;',
			"\n"=>'&#xA;'));
	}
}