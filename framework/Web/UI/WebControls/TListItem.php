<?php
/**
 * TListItem class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package System.Web.UI.WebControls
 */

/**
 * TListItem class.
 *
 * TListItem represents an item in a list control. Each item has a {@link setText Text}
 * property and a {@link setValue Value} property. If either one of them is not set,
 * it will take the value of the other property.
 * An item can be {@link setSelected Selected} or {@link setEnabled Enabled},
 * and it can have additional {@link getAttributes Attributes} which may be rendered
 * if the list control supports so.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class TListItem extends TComponent
{
	/**
	 * @var TMap list of custom attributes
	 */
	private $_attributes=null;
	/**
	 * @var string text of the item
	 */
	private $_text='';
	/**
	 * @var string value of the item
	 */
	private $_value='';
	/**
	 * @var boolean whether the item is enabled
	 */
	private $_enabled=true;
	/**
	 * @var boolean whether the item is selected
	 */
	private $_selected=false;

	/**
	 * Constructor.
	 * @param string text of the item
	 * @param string value of the item
	 * @param boolean whether the item is enabled
	 * @param boolean whether the item is selected
	 */
	public function __construct($text='',$value='',$enabled=true,$selected=false)
	{
		$this->setText($text);
		$this->setValue($value);
		$this->setEnabled($enabled);
		$this->setSelected($selected);
	}

	/**
	 * Returns an array with the names of all variables of this object that should NOT be serialized
	 * because their value is the default one or useless to be cached for the next page loads.
	 * Reimplement in derived classes to add new variables, but remember to  also to call the parent
	 * implementation first.
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		if ($this->_attributes===null)
			$exprops[] = "\0TListItem\0_attributes";
		if($this->_text==='')
			$exprops[] = "\0TListItem\0_text";
		if($this->_value==='')
			$exprops[] = "\0TListItem\0_value";
		if ($this->_enabled===true)
			$exprops[] = "\0TListItem\0_enabled";
		if ($this->_selected===false)
			$exprops[] = "\0TListItem\0_selected";
	}

	/**
	 * @return boolean whether the item is enabled
	 */
	public function getEnabled()
	{
		return $this->_enabled;
	}

	/**
	 * @param boolean whether the item is enabled
	 */
	public function setEnabled($value)
	{
		$this->_enabled=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return boolean whether the item is selected
	 */
	public function getSelected()
	{
		return $this->_selected;
	}

	/**
	 * @param boolean whether the item is selected
	 */
	public function setSelected($value)
	{
		$this->_selected=TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string text of the item
	 */
	public function getText()
	{
		return $this->_text===''?$this->_value:$this->_text;
	}

	/**
	 * @param string text of the item
	 */
	public function setText($value)
	{
		$this->_text=TPropertyValue::ensureString($value);
	}

	/**
	 * @return string value of the item
	 */
	public function getValue()
	{
		return $this->_value===''?$this->_text:$this->_value;
	}

	/**
	 * @param string value of the item
	 */
	public function setValue($value)
	{
		$this->_value=TPropertyValue::ensureString($value);
	}

	/**
	 * @return TAttributeCollection custom attributes
	 */
	public function getAttributes()
	{
		if(!$this->_attributes)
			$this->_attributes=new TAttributeCollection;
		return $this->_attributes;
	}

	/**
	 * @return boolean whether the item has any custom attribute
	 */
	public function getHasAttributes()
	{
		return $this->_attributes && $this->_attributes->getCount()>0;
	}

	/**
	 * @param string name of the attribute
	 * @return boolean whether the named attribute exists
	 */
	public function hasAttribute($name)
	{
		return $this->_attributes?$this->_attributes->contains($name):false;
	}

	/**
	 * @return string the named attribute value, null if attribute does not exist
	 */
	public function getAttribute($name)
	{
		return $this->_attributes?$this->_attributes->itemAt($name):null;
	}

	/**
	 * @param string attribute name
	 * @param string value of the attribute
	 */
	public function setAttribute($name,$value)
	{
		$this->getAttributes()->add($name,$value);
	}

	/**
	 * Removes the named attribute.
	 * @param string the name of the attribute to be removed.
	 * @return string attribute value removed, empty string if attribute does not exist.
	 */
	public function removeAttribute($name)
	{
		return $this->_attributes?$this->_attributes->remove($name):null;
	}
}

