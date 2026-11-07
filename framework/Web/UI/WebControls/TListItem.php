<?php

/**
 * TListItem class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI\WebControls;

use Prado\Collections\TAttributeCollection;
use Prado\Collections\TMap;
use Prado\TPropertyValue;

/**
 * TListItem class.
 *
 * TListItem represents an item in a list control. Each item has a {@see setText Text}
 * property and a {@see setValue Value} property. If either one of them is not set,
 * it will take the value of the other property.
 * An item can be {@see setSelected Selected} or {@see setEnabled Enabled},
 * and it can have additional {@see getAttributes Attributes} which may be rendered
 * if the list control supports so.
 *
 * {@see setContent Content} is HTML rendered inside the `<option>` of a
 * {@see TDropDownList} or {@see TListBox} in place of the encoded Text. The body
 * of a TListItem template tag sets it:
 * ```xml
 * <com:TListItem Value="cat" Text="Cat">
 *   <img src="cat.svg" alt=""> Cat
 * </com:TListItem>
 * ```
 * Content is not encoded; encode user data before adding it. A
 * {@see TCustomizableSelectTrait customizable select} shows the elements of the
 * content. A browser without customizable select support, and the accessible
 * name of the option, use the text of the content, so the content contains the
 * item's text. Decorative images and icons have `alt=""` or `aria-hidden="true"`.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TListItem extends \Prado\TComponent
{
	/**
	 * @var TAttributeCollection list of custom attributes
	 */
	protected $_attributes;
	/**
	 * @var string text of the item
	 */
	protected $_text = '';
	/**
	 * @var string value of the item
	 */
	protected $_value = '';
	/**
	 * @var bool whether the item is enabled
	 */
	protected $_enabled = true;
	/**
	 * @var bool whether the item is selected
	 */
	protected $_selected = false;
	/**
	 * @var string HTML content of the item
	 */
	protected $_content = '';

	/**
	 * Constructor.
	 * @param string $text text of the item
	 * @param string $value value of the item
	 * @param bool $enabled whether the item is enabled
	 * @param bool $selected whether the item is selected
	 */
	public function __construct($text = '', $value = '', $enabled = true, $selected = false)
	{
		$this->setText($text);
		$this->setValue($value);
		$this->setEnabled($enabled);
		$this->setSelected($selected);
		parent::__construct();
	}

	/**
	 * Clones the attributes, so the clone's attributes change independently.
	 * @since 4.4.0
	 */
	public function __clone()
	{
		parent::__clone();
		if ($this->_attributes !== null) {
			$this->_attributes = clone $this->_attributes;
		}
	}

	/**
	 * Returns an array with the names of all variables of this object that should NOT be serialized
	 * because their value is the default one or useless to be cached for the next page loads.
	 * Reimplement in derived classes to add new variables, but remember to  also to call the parent
	 * implementation first.
	 * @param array $exprops by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		if ($this->_attributes === null) {
			$exprops[] = "\0*\0_attributes";
		}
		if ($this->_text === '') {
			$exprops[] = "\0*\0_text";
		}
		if ($this->_value === '') {
			$exprops[] = "\0*\0_value";
		}
		if ($this->_enabled === true) {
			$exprops[] = "\0*\0_enabled";
		}
		if ($this->_selected === false) {
			$exprops[] = "\0*\0_selected";
		}
		if ($this->_content === '') {
			$exprops[] = "\0*\0_content";
		}
	}

	/**
	 * @return bool whether the item is enabled
	 */
	public function getEnabled()
	{
		return $this->_enabled;
	}

	/**
	 * @param bool $value whether the item is enabled
	 */
	public function setEnabled($value)
	{
		$this->_enabled = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return bool whether the item is selected
	 */
	public function getSelected()
	{
		return $this->_selected;
	}

	/**
	 * @param bool $value whether the item is selected
	 */
	public function setSelected($value)
	{
		$this->_selected = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string text of the item
	 */
	public function getText()
	{
		return $this->_text === '' ? $this->_value : $this->_text;
	}

	/**
	 * @param string $value text of the item
	 */
	public function setText($value)
	{
		$this->_text = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string value of the item
	 */
	public function getValue()
	{
		return $this->_value === '' ? $this->_text : $this->_value;
	}

	/**
	 * @param string $value value of the item
	 */
	public function setValue($value)
	{
		$this->_value = TPropertyValue::ensureString($value);
	}

	/**
	 * @return string HTML content rendered inside the option. Defaults to ''.
	 * @since 4.4.0
	 */
	public function getContent()
	{
		return $this->_content;
	}

	/**
	 * Sets the HTML content rendered inside the option in place of the encoded Text.
	 * The content is not encoded.
	 * @param string $value HTML content of the item
	 * @since 4.4.0
	 */
	public function setContent($value)
	{
		$this->_content = trim(TPropertyValue::ensureString($value));
	}

	/**
	 * @return bool whether the item has HTML {@see getContent Content}
	 * @since 4.4.0
	 */
	public function getHasContent()
	{
		return $this->_content !== '';
	}

	/**
	 * Appends template body text to {@see getContent Content}. Other objects are ignored.
	 * @param mixed $object text or object parsed from the template body
	 * @since 4.4.0
	 */
	public function addParsedObject($object)
	{
		if (is_string($object)) {
			$this->setContent($this->_content . $object);
		} else {
			parent::addParsedObject($object);
		}
	}

	/**
	 * @return TAttributeCollection custom attributes
	 */
	public function getAttributes()
	{
		if (!$this->_attributes) {
			$this->_attributes = new TAttributeCollection();
		}
		return $this->_attributes;
	}

	/**
	 * @return bool whether the item has any custom attribute
	 */
	public function getHasAttributes()
	{
		return $this->_attributes && $this->_attributes->getCount() > 0;
	}

	/**
	 * @param string $name name of the attribute
	 * @return bool whether the named attribute exists
	 */
	public function hasAttribute($name)
	{
		return $this->_attributes ? $this->_attributes->contains($name) : false;
	}

	/**
	 * @param mixed $name
	 * @return string the named attribute value, null if attribute does not exist
	 */
	public function getAttribute($name)
	{
		return $this->_attributes ? $this->_attributes->itemAt($name) : null;
	}

	/**
	 * @param string $name attribute name
	 * @param string $value value of the attribute
	 */
	public function setAttribute($name, $value)
	{
		$this->getAttributes()->add($name, $value);
	}

	/**
	 * Removes the named attribute.
	 * @param string $name the name of the attribute to be removed.
	 * @return string attribute value removed, empty string if attribute does not exist.
	 */
	public function removeAttribute($name)
	{
		return $this->_attributes ? $this->_attributes->remove($name) : null;
	}
}
