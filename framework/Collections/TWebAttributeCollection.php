<?php

/**
 * TWebAttributeCollection class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Collections;

use Prado\Prado;

/**
 * TWebAttributeCollection class
 *
 * TWebAttributeCollection is a prefixed map collection for HTML attribute storage.
 *
 * When a prefix is set (e.g., `'data'` or `'aria'`), attribute names are normalized
 * to include it on every read and write, so callers may omit or include the prefix
 * interchangeably. CamelCase names are also converted to dash-separated lowercase.
 *
 * ```php
 * $attrs = new TWebAttributeCollection('data');
 * $attrs->setAttribute('itemId', 'myElement'); // stored as 'data-item-id'
 * $attrs->getAttribute('item-id');             // returns 'myElement'
 * $attrs['custom'] = 'value';                  // stored as 'data-custom'
 * ```
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TWebAttributeCollection extends TMap
{
	/**
	 * @var string
	 */
	protected $_prefix = '';


	//	-----  TMap Overrides - adds prefix if not present -----

	/**
	 * Constructs a new collection with the given attribute prefix and optional initial attributes.
	 *
	 * @param string $prefix attribute prefix without dash (e.g., `'data'`, `'aria'`), or `''` for none
	 * @param null|array|TMap $attributes initial attributes to populate
	 */
	public function __construct($prefix = '', $attributes = null)
	{
		$this->_prefix = strtolower(rtrim($prefix, '-'));
		parent::__construct($attributes);
	}

	/**
	 * Returns the item with the specified key after normalizing the key with the prefix.
	 *
	 * @param mixed $key the key
	 * @return mixed the element at the key, null if not found
	 */
	public function itemAt($key)
	{
		$key = $this->normalizeDataAttributePrefix($key);
		return parent::itemAt($key);
	}

	/**
	 * Adds an item to the map after normalizing the key with the prefix.
	 *
	 * An existing entry at the same normalized key is overwritten.
	 *
	 * @param mixed $key attribute name (with or without prefix)
	 * @param mixed $value attribute value
	 * @return mixed the normalized key under which the item was stored
	 */
	public function add($key, $value): mixed
	{
		$key = $this->normalizeDataAttributePrefix($key);
		return parent::add($key, $value);
	}

	/**
	 * Removes an item from the map by its key.
	 * @param mixed $key the key of the item to be removed
	 * @return mixed the removed value, null if no such key exists.
	 */
	public function remove($key)
	{
		$key = $this->normalizeDataAttributePrefix($key);
		return parent::remove($key);
	}

	/**
	 * Returns whether the map contains an item with the specified key, after normalizing the key with the prefix.
	 *
	 * @param mixed $key the key
	 * @return bool whether the map contains an item with the specified key
	 */
	public function contains($key): bool
	{
		$key = $this->normalizeDataAttributePrefix($key);
		return parent::contains($key);
	}

	//	-----  Web Attribute methods  -----

	/**
	 * Gets the attribute prefix.
	 * @return string the attribute prefix (e.g., "data" or "aria")
	 */
	public function getPrefix()
	{
		return $this->_prefix;
	}

	/**
	 * Returns the attribute prefix with a trailing dash (e.g., `'data-'`), or empty string if no prefix is set.
	 *
	 * @return string the prefix with trailing dash, or `''`
	 */
	public function getPrefixDash()
	{
		$prefix = $this->getPrefix();
		if (empty($prefix)) {
			return '';
		}
		return $prefix . '-';
	}

	/**
	 * Checks if an attribute has been explicitly set.
	 * @param string $name attribute name
	 * @return bool whether the attribute has been explicitly set (even if empty)
	 */
	public function hasAttribute($name)
	{
		return $this->contains($name);
	}

	/**
	 * Gets an attribute value.
	 * @param string $name attribute name
	 * @return ?string attribute value, or null if not set
	 */
	public function getAttribute($name)
	{
		return $this->itemAt($name);
	}

	/**
	 * Sets an attribute value.
	 *
	 * Both name and value are trimmed. Passing an empty value removes the attribute.
	 *
	 * @param string $name attribute name
	 * @param string $value attribute value; empty string clears the attribute
	 */
	public function setAttribute($name, $value)
	{
		$name = trim((string) $name);
		$value = trim((string) $value);
		if ($value === '') {
			$this->remove($name);
		} else {
			$this->add($name, $value);
		}
	}

	/**
	 * Removes an attribute.
	 * @param string $name attribute name to remove
	 */
	public function clearAttribute($name)
	{
		$this->remove($name);
	}

	/**
	 * @return array<string,string> all explicitly set attribute name-value pairs
	 */
	public function getAttributes()
	{
		return $this->toArray();
	}

	/**
	 * Clears all attributes from the collection.
	 */
	public function reset()
	{
		$this->clear();
	}

	/**
	 * Normalizes an attribute name by adding the prefix and lowercasing.
	 *
	 * Underscores are converted to dashes. CamelCase letters insert a dash before each
	 * uppercase character when $useCamelCase is true. The prefix is added if absent.
	 *
	 * @param string $attrName the attribute name to normalize
	 * @param bool $useCamelCase whether to expand camelCase into dash-separated segments
	 * @return string the normalized, prefixed attribute name
	 */
	public function normalizeDataAttributePrefix($attrName, $useCamelCase = true)
	{
		$prefix = $this->getPrefixDash();
		$len = strlen($prefix);

		$attrName = str_replace('_', '-', $attrName);

		if (!empty($prefix) && strncasecmp($attrName, $prefix, $len) === 0) {
			$attrName = substr($attrName, $len);
		}

		if ($useCamelCase && strpos($attrName, '-') === false && strtoupper($attrName) !== $attrName) {
			$attrName = preg_replace('/(?<!^)[A-Z]/', '-$0', $attrName);
		}

		return $prefix . strtolower($attrName);
	}

	/**
	 * Strips the prefix from an attribute name and lowercases the result.
	 *
	 * @param string $attrName the attribute name to strip
	 * @param bool $useCamelCase when true, converts the remaining dashes to camelCase
	 * @return string the attribute name without prefix
	 */
	public function stripDataAttributePrefix($attrName, $useCamelCase = false)
	{
		$prefix = $this->getPrefixDash();
		if (!empty($prefix) && (strncasecmp($attrName, $prefix, $len = strlen($prefix)) === 0)) {
			$attrName = substr($attrName, $len);
		}
		$attrName = strtolower($attrName);
		if ($useCamelCase) {
			$attrName = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $attrName))));
		}
		return $attrName;
	}


	/**
	 * Writes all attributes to the HTML writer.
	 *
	 * @param \Prado\Web\UI\THtmlWriter $writer the HTML writer to add attributes to
	 */
	public function addAttributesToRender($writer)
	{
		$writer->addAttributes($this->getAttributes());
	}

	// ----- get/set method Access

	/**
	 * Dispatches `getXxx()` / `setXxx()` calls to {@see getAttribute} / {@see setAttribute}.
	 *
	 * @param string $method method name
	 * @param array $args method arguments
	 * @return mixed attribute value for getters, null for setters
	 */
	public function __call($method, $args)
	{

		if (strncasecmp($method, 'get', 3) === 0) {
			$propname = substr($method, 3);
			$propname = ltrim($this->methodToAttributeName($propname), '-');
			return $this->getAttribute($propname);
		} elseif ((strncasecmp($method, 'set', 3) === 0) && count($args) === 1) {
			$propname = substr($method, 3);
			$propname = ltrim($this->methodToAttributeName($propname), '-');
			return $this->setAttribute($propname, $args[0]);
		}
		return parent::__call($method, $args);
	}

	// ----- get/set property Access

	/**
	 * Magic getter for attribute access via $collection->property.
	 *
	 * @param string $name property/attribute name
	 * @return mixed the attribute value
	 */
	public function __get($name)
	{
		if (Prado::method_visible($this, $getter = 'get' . $name)) {
			return $this->$getter();
		}
		$name = $this->methodToAttributeName($name);
		return $this->getAttribute($name);
	}

	/**
	 * Magic setter for attribute access via $collection->property = value.
	 *
	 * @param string $name property/attribute name
	 * @param mixed $value value to set
	 * @return void
	 */
	public function __set($name, $value)
	{
		if (Prado::method_visible($this, $setter = 'set' . $name)) {
			return $this->$setter($value);
		}
		$name = $this->methodToAttributeName($name);
		return $this->setAttribute($name, $value);
	}

	/**
	 * Converts a PHP method or property name to an HTML attribute name.
	 *
	 * Underscores become dashes; a dash is inserted before each interior uppercase letter;
	 * the result is lowercased.
	 *
	 * @param string $name the method/property name
	 * @return string the converted attribute name
	 */
	protected function methodToAttributeName($name)
	{
		$name = str_replace('_', '-', $name);
		$name = preg_replace('/(?<!^)[A-Z]/', '-$0', $name);
		return strtolower($name);
	}

	/**
	 * Returns true; every name is readable as an attribute via {@see __get}.
	 *
	 * @param string $name property name
	 * @return bool always true
	 */
	public function canGetProperty($name)
	{
		return true;
	}

	/**
	 * Returns true; every name is writable as an attribute via {@see __set}.
	 *
	 * @param string $name property name
	 * @return bool always true
	 */
	public function canSetProperty($name)
	{
		return true;
	}

	/**
	 * Checks if a method exists or is a dynamic get/set method.
	 * @param string $name method name
	 * @return bool whether the method exists
	 */
	public function hasMethod($name)
	{
		if (parent::hasMethod($name)) {
			return true;
		}
		if ((strncasecmp($name, 'get', 3) === 0) || (strncasecmp($name, 'set', 3) === 0)) {
			return true;
		}
		return false;
	}

	/**
	 * Excludes default-valued or empty properties from serialization to reduce the serialized payload.
	 *
	 * @param array $exprops by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		if ($this->_prefix === '') {
			$exprops[] = "\0" . __CLASS__ . "\0_prefix";
		}
	}
}
