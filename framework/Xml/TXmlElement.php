<?php

/**
 * TXmlElement class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Xml;

use Prado\TPropertyValue;
use Prado\Collections\TList;
use Prado\Collections\TMap;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;

/**
 * TXmlElement class.
 *
 * TXmlElement represents an XML element node.
 * You can obtain its tag-name, attributes, text between the opening and closing
 * tags via the TagName, Attributes, and Value properties, respectively.
 * You can also retrieve its parent and child elements by Parent and Elements
 * properties, respectively.
 *
 * This class implements important DOM properties and methods for better compatibility
 * with standard DOM access. This is the preferred method of access.
 *
 * XPath expressions are evaluated through the `xpath()` method for querying
 * elements within the XML structure using standard XPath syntax.
 *
 * This class implements the IteratorAggregate, ArrayAccess, and Countable interfaces
 * for traversing, accessing, and counting XML elements.  These are not standard but
 * provided for convenience.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Brad Anderson <belisoful@icloud.com> xpath, cloning, DOM-compatibility
 * @since 3.0
 */
class TXmlElement extends \Prado\TComponent implements \IteratorAggregate, \ArrayAccess, \Countable
{
	/**
	 * @const string The prefix for internal "prado xml id" attributes.
	 * 	This is used in `xpath()`.
	 */
	private const XML_ID_ATTR_PREFIX = 'prado-xml-id-';

	/**
	 * @const int This provides "getElement[s]By*" for only searching in the
	 *  current Element instance.
	 */
	public const SEARCH_ELEMENT = 0;

	/**
	 * @const int This provides "getElement[s]By*" for recursively searching
	 *  all children, depth first. Depth First is top to bottom search, regardless
	 *  of depth.
	 */
	public const SEARCH_DEPTH_FIRST = 1;

	/**
	 * @const int This provides "getElement[s]By*" for breadth-first search.
	 *  All nodes at depth N are visited before any node at depth N + 1.
	 */
	public const SEARCH_BREADTH_FIRST = 2;

	/**
	 * @var TXmlElement parent of this element
	 */
	private ?TXmlElement $_parent = null;
	/**
	 * @var string tag-name of this element
	 */
	private string $_tagName = 'unknown';
	/**
	 * @var string text enclosed between opening and closing tags of this element
	 */
	private ?string $_value = null;
	/**
	 * @var TXmlElementList list of child elements of this element
	 */
	private ?TXmlElementList $_elements = null;
	/**
	 * @var TMap attributes of this element
	 */
	private ?TMap $_attributes = null;

	/**
	 * Constructor.
	 * Initializes a new XML element with the specified tag name.
	 * @param string $tagName Tag name for this element
	 * @throws TInvalidDataTypeException when $tagName is null
	 * @throws TInvalidDataValueException when $tagName is empty
	 */
	public function __construct(string $tagName)
	{
		$this->setTagName($tagName);
		parent::__construct();
	}

	/**
	 * Validates the tag name for this element.
	 * @return bool Whether the tag name is valid
	 * @since 4.3.3
	 */
	protected function validateTagName(): bool
	{
		return true;
	}

	/**
	 * Gets the parent element of this element.
	 * @return ?TXmlElement Parent element of this element, or null if none
	 */
	public function getParent(): ?TXmlElement
	{
		return $this->_parent;
	}

	/**
	 * Sets the parent element of this element.
	 * @param ?TXmlElement $parent Parent element of this element
	 */
	public function setParent(?TXmlElement $parent): void
	{
		$this->_parent = $parent;
	}

	/**
	 * Gets the tag name of this element.
	 * @return string Tag name of this element
	 */
	public function getTagName(): string
	{
		return $this->_tagName;
	}

	/**
	 * Sets the tag name of this element.
	 * @param string $tagName Tag name of this element
	 * @throws TInvalidDataTypeException when $tagName is null
	 * @throws TInvalidDataValueException when $tagName is empty
	 */
	public function setTagName(string $tagName): void
	{
		if ($tagName === null) {
			throw new TInvalidDataTypeException('xmlelement_null_tag');
		}
		if ($this->validateTagName() && $tagName == '') {
			throw new TInvalidDataValueException('xmlelement_empty_tag');
		}
		$this->_tagName = $tagName;
	}

	/**
	 * Gets the text content enclosed between opening and closing tags of this element.
	 * @return ?string Text content of this element, or null if none
	 */
	public function getValue(): ?string
	{
		return $this->_value;
	}

	/**
	 * Sets the text content enclosed between opening and closing tags of this element.
	 * @param ?string $value Text content of this element
	 */
	public function setValue(?string $value): void
	{
		if ($value !== null) {
			$this->_value = TPropertyValue::ensureString($value);
		} else {
			$this->_value = null;
		}
	}

	/**
	 * Gets the list of attributes for this element.
	 * @return TMap List of attributes for this element
	 */
	public function getAttributes(): TMap
	{
		if (!$this->_attributes) {
			$this->_attributes = new TMap();
		}
		return $this->_attributes;
	}

	/**
	 * Determines whether this element has any attributes.
	 * @return bool True if this element has attributes, false otherwise
	 */
	public function getHasAttribute()
	{
		return $this->_attributes !== null && $this->_attributes->getCount() > 0;
	}

	/**
	 * Determines whether the attribute with the specified name exists.
	 * @param string $name Attribute name
	 * @return bool True if the attribute exists, false otherwise
	 * @since 4.3.3
	 */
	public function hasAttribute($name): bool
	{
		if ($this->_attributes !== null) {
			return $this->_attributes->contains($name);
		}
		return false;
	}

	/**
	 * Gets the value of the attribute with the specified name.
	 * @param string $name Attribute name
	 * @return ?string Attribute value, or null if no such attribute exists
	 */
	public function getAttribute(string $name): ?string
	{
		if ($this->_attributes !== null) {
			return $this->_attributes->itemAt($name);
		} else {
			return null;
		}
	}

	/**
	 * Sets an attribute value.
	 * @param string $name Attribute name
	 * @param string $value Attribute value
	 */
	public function setAttribute(string $name, $value)
	{
		$this->getAttributes()->add($name, TPropertyValue::ensureString($value));
	}

	/**
	 * Removes an attribute from this element.
	 * @param string $name Attribute name
	 * @since 4.3.3
	 */
	public function removeAttribute(string $name): void
	{
		if ($this->_attributes !== null) {
			$this->_attributes->remove($name);
		}
	}

	/**
	 * Gets the list of child elements of this element.
	 * @return TXmlElementList List of child elements
	 */
	public function getElements(): TXmlElementList
	{
		if (!$this->_elements) {
			$this->_elements = new TXmlElementList($this);
		}
		return $this->_elements;
	}

	/**
	 * Determines whether this element has any child elements.
	 * @return bool True if this element has child elements, false otherwise
	 */
	public function getHasElement(): bool
	{
		return $this->_elements !== null && $this->_elements->getCount() > 0;
	}

	/**
	 * Gets the child element at the specified index.
	 * @param int $index Index of the child elements to return
	 * @return ?TXmlElement The element at a specific index, or null if index is invalid
	 * @since 4.3.3
	 */
	public function getElementAtIndex(int $index): ?TXmlElement
	{
		if ($this->_elements && $index >= 0 && $index < $this->_elements->getCount()) {
			return $this->_elements->itemAt($index);
		}
		return null;
	}

	/**
	 * Gets the first child element that has the specified tag name.
	 * @param string $tagName Tag name to search for
	 * @param int $options Search type (SEARCH_ELEMENT, SEARCH_DEPTH_FIRST, or SEARCH_BREADTH_FIRST)
	 * @return ?TXmlElement First child element with the tag name, or null if not found
	 */
	public function getElementByTagName(string $tagName, int $options = TXmlElement::SEARCH_ELEMENT): ?TXmlElement
	{
		if ($this->_elements) {
			if ($options === TXmlElement::SEARCH_BREADTH_FIRST) {
				$queue = [];
				foreach ($this->_elements as $element) {
					$queue[] = $element;
				}
				while (!empty($queue)) {
					$element = array_shift($queue);
					if ($element->_tagName === $tagName) {
						return $element;
					}
					if ($element->_elements) {
						foreach ($element->_elements as $child) {
							$queue[] = $child;
						}
					}
				}
				return null;
			}

			foreach ($this->_elements as $element) {
				if ($element->_tagName === $tagName) {
					return $element;
				}
				if ($options === TXmlElement::SEARCH_DEPTH_FIRST) {
					if (($result = $element->getElementByTagName($tagName, $options))) {
						return $result;
					}
				}
			}
		}
		return null;
	}

	/**
	 * Gets all child elements that have the specified tag name.
	 * @param string $tagName Tag name to search for
	 * @param int $options Search type (SEARCH_ELEMENT, SEARCH_DEPTH_FIRST, or SEARCH_BREADTH_FIRST)
	 * @param ?TList $results The recursive list to add results to (optional).
	 * @return TList List of all child elements with the tag name
	 */
	public function getElementsByTagName(string $tagName, int $options = TXmlElement::SEARCH_ELEMENT, ?TList $results = null): TList
	{
		if (!$results) {
			$results = new TList();
		}
		if ($this->_elements) {
			if ($options === TXmlElement::SEARCH_BREADTH_FIRST) {
				$queue = [];
				foreach ($this->_elements as $element) {
					$queue[] = $element;
				}
				while (!empty($queue)) {
					$element = array_shift($queue);
					if ($element->_tagName === $tagName) {
						$results->add($element);
					}
					if ($element->_elements) {
						foreach ($element->_elements as $child) {
							$queue[] = $child;
						}
					}
				}
				return $results;
			}

			foreach ($this->_elements as $element) {
				if ($element->_tagName === $tagName) {
					$results->add($element);
				}
				if ($options === TXmlElement::SEARCH_DEPTH_FIRST) {
					$element->getElementsByTagName($tagName, $options, $results);
				}
			}
		}
		return $results;
	}

	/**
	 * Gets the first child element that has the specified attribute name and optional value.
	 * @param string $name Attribute name to search for
	 * @param ?string $value Attribute value to match (null matches any attribute with the name)
	 * @param int $options Search type (SEARCH_ELEMENT, SEARCH_DEPTH_FIRST, or SEARCH_BREADTH_FIRST)
	 * @return ?TXmlElement First child element matching the attribute, or null if not found
	 * @since 4.3.3
	 */
	public function getElementByAttribute(string $name, ?string $value, int $options = TXmlElement::SEARCH_ELEMENT): ?TXmlElement
	{
		if ($this->_elements) {
			if ($options === TXmlElement::SEARCH_BREADTH_FIRST) {
				$queue = [];
				foreach ($this->_elements as $element) {
					$queue[] = $element;
				}
				while (!empty($queue)) {
					$element = array_shift($queue);
					if ($element->hasAttribute($name) && ($value === null || $element->getAttribute($name) === $value)) {
						return $element;
					}
					if ($element->_elements) {
						foreach ($element->_elements as $child) {
							$queue[] = $child;
						}
					}
				}
				return null;
			}

			foreach ($this->_elements as $element) {
				if ($element->hasAttribute($name) && ($value === null || $element->getAttribute($name) === $value)) {
					return $element;
				}
				if ($options === TXmlElement::SEARCH_DEPTH_FIRST) {
					if (($result = $element->getElementByAttribute($name, $value, $options))) {
						return $result;
					}
				}
			}
		}
		return null;
	}

	/**
	 * Gets all child elements that have the specified attribute name and optional value.
	 * @param string $name Attribute name to search for
	 * @param ?string $value Attribute value to match (null matches any attribute with the name)
	 * @param int $options Search type (SEARCH_ELEMENT, SEARCH_DEPTH_FIRST, or SEARCH_BREADTH_FIRST)
	 * @param ?TList $results Optional list to add results to.
	 * @return TList List of all child elements matching the attribute
	 * @since 4.3.3
	 */
	public function getElementsByAttribute(string $name, ?string $value, int $options = TXmlElement::SEARCH_ELEMENT, ?TList $results = null): TList
	{
		if (!$results) {
			$results = new TList();
		}
		if ($this->_elements) {
			if ($options === TXmlElement::SEARCH_BREADTH_FIRST) {
				$queue = [];
				foreach ($this->_elements as $element) {
					$queue[] = $element;
				}
				while (!empty($queue)) {
					$element = array_shift($queue);
					if ($element->hasAttribute($name) && ($value === null || $element->getAttribute($name) === $value)) {
						$results->add($element);
					}
					if ($element->_elements) {
						foreach ($element->_elements as $child) {
							$queue[] = $child;
						}
					}
				}
				return $results;
			}
			foreach ($this->_elements as $element) {
				if ($element->hasAttribute($name) && ($value === null || $element->getAttribute($name) === $value)) {
					$results->add($element);
				}
				if ($options === TXmlElement::SEARCH_DEPTH_FIRST) {
					$element->getElementsByAttribute($name, $value, $options, $results);
				}
			}
		}
		return $results;
	}

	/**
	 * Inserts XML ID attribute for XPath support.
	 * This method is used internally by the xpath() method to support XPath queries.
	 * @param ?string $attrName The attribute name to use (if null, generates a random one)
	 * @param int $index The index counter for unique IDs
	 * @return string The attribute name used
	 * @since 4.3.3
	 */
	protected function insertPradoXmlId(?string $attrName = null, int &$index = 0): string
	{
		if (!$attrName) {
			$attrName = TXmlElement::XML_ID_ATTR_PREFIX . mt_rand();
		}
		$this->setAttribute($attrName, (string) $index++);

		if ($this->getHasElement()) {
			foreach ($this->_elements as $element) {
				$element->insertPradoXmlId($attrName, $index);
			}
		}
		return $attrName;
	}

	/**
	 * Removes XML ID attribute used for XPath support.
	 * This method is used internally by the xpath() method to clean up XPath support attributes.
	 * @param string $attrName The attribute name to remove
	 * @since 4.3.3
	 */
	protected function removePradoXmlId(string $attrName): void
	{
		$this->removeAttribute($attrName);

		if ($this->getHasElement()) {
			foreach ($this->_elements as $element) {
				$element->removePradoXmlId($attrName);
			}
		}
	}

	/**
	 * Find elements matching an XPath expression.
	 * This method allows you to use XPath expressions to query elements within the XML structure.
	 * @param string $xpath XPath expression
	 * @return TList List of matching elements
	 * @since 4.3.3
	 */
	public function xpath(string $xpath): TList
	{
		$idAttrName = $this->insertPradoXmlId();

		$doc = new \DOMDocument();
		$doc->loadXML($this->toString(0, false));

		$xpathObj = new \DOMXPath($doc);
		$nodes = $xpathObj->query($xpath);

		$results = new TList();
		foreach ($nodes as $node) {
			if ($node instanceof \DOMElement) {
				$xmlId = $node->getAttribute($idAttrName);

				// @todo This can be sped up with a private focused search function due to the known ordering of each node $xmlId.
				$element = $this->getElementByAttribute($idAttrName, $xmlId, TXmlElement::SEARCH_BREADTH_FIRST);
				if ($element) {
					$results->add($element);
				}
			}
		}

		$this->removePradoXmlId($idAttrName);

		return $results;
	}

	/**
	 * Creates and returns a clone of this element.
	 * The clone includes all child elements and attributes.
	 * @since 4.3.3
	 */
	public function __clone(): void
	{
		parent::__clone();

		$this->_parent = null;
		$this->setTagName($this->getTagName());
		$this->setValue($this->getValue());

		// Clone attributes
		if ($this->_attributes) {
			$this->_attributes = clone $this->_attributes;
		}

		// Clone child elements
		if ($this->_elements) {
			$oldElementList = $this->_elements;

			$this->_elements = null;
			$elementList = $this->getElements();

			foreach ($oldElementList as $element) {
				$elementList->add(clone $element);
			}
		}
	}

	/**
	 * Creates a string representation of this element.
	 * This method returns the XML representation of this element including all attributes and child elements.
	 * @param int $indent Indentation level for pretty printing
	 * @param bool $excludeInternalId Whether to exclude internal ID attributes
	 * @return string String representation of this element
	 */
	public function toString(int $indent = 0, bool $excludeInternalId = true): string
	{
		$attr = '';
		if ($this->_attributes !== null) {
			$len = strlen(TXmlElement::XML_ID_ATTR_PREFIX);
			// Add attributes
			foreach ($this->_attributes as $name => $value) {
				if ($excludeInternalId && strncmp($name, TXmlElement::XML_ID_ATTR_PREFIX, $len) === 0) {
					continue;
				}
				$value = $this->xmlEncode($value);
				$attr .= " $name=\"$value\"";
			}
		}

		$prefix = str_repeat(' ', $indent * 4);
		if ($this->getHasElement()) {
			$str = $prefix . "<{$this->_tagName}$attr>\n";
			foreach ($this->getElements() as $element) {
				$str .= $element->toString($indent + 1, $excludeInternalId) . "\n";
			}
			$str .= $prefix . "</{$this->_tagName}>";
			return $str;
		} elseif (($value = $this->getValue()) != null) { // skip blank and null
			$value = $this->xmlEncode($value);
			return $prefix . "<{$this->_tagName}$attr>$value</{$this->_tagName}>";
		} else {
			return $prefix . "<{$this->_tagName}$attr />";
		}
	}

	/**
	 * Magic-method override. Called whenever this element is used as a string.
	 * ```php
	 * $element = new TXmlElement('tag');
	 * echo $element;
	 * ```
	 * or
	 * ```php
	 * $element = new TXmlElement('tag');
	 * $xml = (string)$element;
	 * ```
	 * @return string string representation of this element
	 */
	public function __toString(): string
	{
		return $this->toString();
	}


	/**
	 * Encodes a non-xml character into its xml equivalent.
	 * @param string $str The string to encode
	 * @return string The encoded character.
	 */
	private function xmlEncode(string $str): string
	{
		return strtr($str, [
			'>' => '&gt;',
			'<' => '&lt;',
			'&' => '&amp;',
			'"' => '&quot;',
			"\r" => '&#xD;',
			"\t" => '&#x9;',
			"\n" => '&#xA;',
			"\x00" => '&#x00;',
			"\x01" => '&#x01;',
			"\x02" => '&#x02;',
			"\x03" => '&#x03;',
			"\x04" => '&#x04;',
			"\x05" => '&#x05;',
			"\x06" => '&#x06;',
			"\x07" => '&#x07;',
			"\x08" => '&#x08;',
			"\x0B" => '&#x0B;',
			"\x0C" => '&#x0C;',
			"\x0E" => '&#x0E;',
			"\x0F" => '&#x0F;',
			"\x10" => '&#x10;',
			"\x11" => '&#x11;',
			"\x12" => '&#x12;',
			"\x13" => '&#x13;',
			"\x14" => '&#x14;',
			"\x15" => '&#x15;',
			"\x16" => '&#x16;',
			"\x17" => '&#x17;',
			"\x18" => '&#x18;',
			"\x19" => '&#x19;',
			"\x1A" => '&#x1A;',
			"\x1B" => '&#x1B;',
			"\x1C" => '&#x1C;',
			"\x1D" => '&#x1D;',
			"\x1E" => '&#x1E;',
			"\x1F" => '&#x1F;',
		]);
	}


	//	From \Countable

	/**
	 * Returns the number of elements in the child list.
	 * This method is required by the \Countable interface.
	 * @return int Number of child elements in this Xml Element.
	 * @see https://www.php.net/manual/en/class.countable.php
	 * @since 4.3.3
	 */
	public function count(): int
	{
		return $this->getCount();
	}

	/**
	 * Returns the number of elements in the child list.
	 * This is the `count` property get- function.
	 * @return int Number of child elements in this Xml Element.
	 * @since 4.3.3
	 */
	public function getCount(): int
	{
		if (!$this->_elements) {
			return 0;
		}
		return $this->_elements->getCount();
	}


	//	From \ArrayAccess

	/**
	 * Returns whether there is an item at the specified offset.
	 * This method is required by the \ArrayAccess interface.
	 * @param int $offset The offset to check on
	 * @return bool Whether an item exists at the specified offset
	 * @see https://www.php.net/manual/en/class.arrayaccess.php
	 * @since 4.3.3
	 */
	public function offsetExists($offset): bool
	{
		if (!$this->_elements) {
			return false;
		}
		return $this->_elements->offsetExists($offset);
	}

	/**
	 * Returns the child element at the specified offset.
	 * This method is required by the \ArrayAccess interface.
	 * @param int $offset The offset to retrieve item.
	 * @throws TInvalidDataValueException if the offset is invalid
	 * @return TXmlElement The item at the offset
	 * @see https://www.php.net/manual/en/class.arrayaccess.php
	 * @since 4.3.3
	 */
	public function offsetGet($offset): mixed
	{
		if (!$this->_elements) {
			throw new TInvalidDataValueException('list_index_invalid', $offset);
		}
		return $this->getElements()->itemAt($offset);
	}

	/**
	 * Sets the child element at the specified offset.
	 * This method is required by the \ArrayAccess interface.
	 * @param int $offset The offset to set item
	 * @param TXmlElement $item The item value
	 * @see https://www.php.net/manual/en/class.arrayaccess.php
	 * @since 4.3.3
	 */
	public function offsetSet($offset, $item): void
	{
		$this->getElements()->offsetSet($offset, $item);
	}

	/**
	 * Unsets the child element at the specified offset.
	 * This method is required by the \ArrayAccess interface.
	 * @param int $offset The offset to unset item
	 * @see https://www.php.net/manual/en/class.arrayaccess.php
	 * @since 4.3.3
	 */
	public function offsetUnset($offset): void
	{
		if (!$this->_elements) {
			throw new TInvalidDataValueException('list_index_invalid', $offset);
		}
		$this->getElements()->offsetUnset($offset);
	}


	//	From \IteratorAggregate

	/**
	 * Returns an iterator for traversing the children elements.
	 * This method is required by the \IteratorAggregate interface.
	 * @return \Iterator An iterator for traversing the children elements.
	 * @see https://www.php.net/manual/en/class.iteratoraggregate.php
	 * @since 4.3.3
	 */
	public function getIterator(): \Traversable
	{
		if (!$this->_elements) {
			return new \ArrayIterator([]);
		}
		return $this->_elements->getIterator();
	}


	//	From \DOMElement

	/**
	 * Gets the first child element.
	 * This method mimics the DOMElement::firstElementChild property.
	 * @return ?TXmlElement First child element or null if none exists
	 * @see https://www.php.net/manual/en/class.domelement.php
	 * @since 4.3.3
	 */
	public function getFirstELementChild(): ?TXmlElement
	{
		if ($this->_elements && $this->_elements->getCount() > 0) {
			return $this->_elements->itemAt(0);
		}
		return null;
	}

	/**
	 * Gets the last child element.
	 * This method mimics the DOMElement::lastElementChild property.
	 * @return ?TXmlElement Last child element or null if none exists
	 * @see https://www.php.net/manual/en/class.domelement.php
	 * @since 4.3.3
	 */
	public function getLastElementChild(): ?TXmlElement
	{
		if ($this->_elements && $this->_elements->getCount() > 0) {
			return $this->_elements->itemAt($this->_elements->getCount() - 1);
		}
		return null;
	}

	/**
	 * Gets the number of child elements.
	 * This method mimics the DOMElement::childElementCount property.
	 * @return int The number of items in the list
	 * @see https://www.php.net/manual/en/class.domelement.php
	 * @since 4.3.3
	 */
	public function childElementCount(): int
	{
		if (!$this->_elements) {
			return 0;
		}
		return $this->_elements->getCount();
	}

	/**
	 * Gets the previous sibling element.
	 * This method mimics the DOMElement::previousElementSibling property.
	 * @return ?TXmlElement Previous sibling element or null if none exists
	 * @see https://www.php.net/manual/en/class.domelement.php
	 * @since 4.3.3
	 */
	public function getPreviousElementSibling(): ?TXmlElement
	{
		if ($this->_parent === null) {
			return null;
		}
		$elements = $this->_parent->getElements();
		$index = $elements->indexOf($this);
		if ($index !== false && $index > 0) {
			return $elements->itemAt($index - 1);
		}
		return null;
	}

	/**
	 * Gets the next sibling element.
	 * This method mimics the DOMElement::nextElementSibling property.
	 * @return ?TXmlElement Next sibling element or null if none exists
	 * @see https://www.php.net/manual/en/class.domelement.php
	 * @since 4.3.3
	 */
	public function getNextElementSibling(): ?TXmlElement
	{
		if ($this->_parent === null) {
			return null;
		}
		$elements = $this->_parent->getElements();
		$index = $elements->indexOf($this);
		if ($index !== false && $index < $elements->getCount() - 1) {
			return $elements->itemAt($index + 1);
		}
		return null;
	}


	//	From \DOMNode

	/**
	 * Gets the tag name of this node.
	 * This method mimics the DOMNode::nodeName property.
	 * @return string The Tag Name
	 * @see https://www.php.net/manual/en/class.domnode.php
	 * @since 4.3.3
	 */
	public function getNodeName(): string
	{
		return $this->getTagName();
	}

	/**
	 * Gets the value of this node.
	 * This method mimics the DOMNode::nodeValue property.
	 * @return ?string The Value of the Element
	 * @see https://www.php.net/manual/en/class.domnode.php
	 * @since 4.3.3
	 */
	public function getNodeValue(): ?string
	{
		return $this->getValue();
	}

	/**
	 * Sets the value of this node.
	 * This method mimics the DOMNode::nodeValue property setter.
	 * @param ?string $value The Value of the Element
	 * @see https://www.php.net/manual/en/class.domnode.php
	 * @since 4.3.3
	 */
	public function setNodeValue(?string $value): void
	{
		$this->setValue($value);
	}

	/**
	 * Gets the node type.
	 * This method mimics the DOMNode::nodeType property.
	 * @return int The type of the Element.
	 * @see https://www.php.net/manual/en/class.domnode.php
	 * @since 4.3.3
	 */
	public function getNodeType(): int
	{
		return XML_ENTITY_NODE;
	}

	/**
	 * Gets the parent element of this node.
	 * This method mimics the DOMNode::parentElement property.
	 * @return ?TXmlElement The parent element, or null if none
	 * @see https://www.php.net/manual/en/class.domnode.php
	 * @since 4.3.3
	 */
	public function getParentElement(): ?TXmlElement
	{
		return $this->getParent();
	}

	/**
	 * Gets the child nodes of this node.
	 * This method mimics the DOMNode::childNodes property.
	 * @return ?TXmlElementList The list of child elements.
	 * @see https://www.php.net/manual/en/class.domnode.php
	 * @since 4.3.3
	 */
	public function getChildNodes(): ?TXmlElementList
	{
		return $this->getElements();
	}

	/**
	 * Determines whether this node has child nodes.
	 * This method mimics the DOMNode::hasChildNodes method.
	 * @return ?bool Whether there are child nodes.
	 * @see https://www.php.net/manual/en/class.domnode.php
	 * @since 4.3.3
	 */
	public function hasChildNodes(): ?bool
	{
		return $this->getHasElement();
	}
}
