<?php
/**
 * TAttributeCollection classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Collections
 */

/**
 * Includes TMap class
 */
Prado::using('System.Collections.TMap');

/**
 * TAttributeCollection class
 *
 * TAttributeCollection implements a collection for storing attribute names and values.
 *
 * Besides all functionalities provided by {@link TMap}, TAttributeCollection
 * allows you to get and set attribute values like getting and setting
 * properties. For example, the following usages are all valid for a
 * TAttributeCollection object:
 * <code>
 * $collection->Text='text';
 * echo $collection->Text;
 * </code>
 * They are equivalent to the following:
 * <code>
 * $collection->add('Text','text');
 * echo $collection->itemAt('Text');
 * </code>
 *
 * Note, attribute names are case-insensitive. They are converted to lower-case
 * in the collection storage.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Collections
 * @since 3.0
 */
class TAttributeCollection extends TMap
{
	/**
	 * Returns a property value or an event handler list by property or event name.
	 * This method overrides the parent implementation by returning
	 * a key value if the key exists in the collection.
	 * @param string the property name or the event name
	 * @return mixed the property value or the event handler list
	 * @throws TInvalidOperationException if the property/event is not defined.
	 */
	public function __get($name)
	{
		$name=strtolower($name);
		return $this->contains($name)?$this->itemAt($name):parent::__get($name);
	}

	/**
	 * Sets value of a component property.
	 * This method overrides the parent implementation by adding a new key value
	 * to the collection.
	 * @param string the property name or event name
	 * @param mixed the property value or event handler
	 * @throws TInvalidOperationException If the property is not defined or read-only.
	 */
	public function __set($name,$value)
	{
		$this->add(strtolower($name),$value);
	}

	/**
	 * Determines whether a property is defined.
	 * This method overrides parent implementation by returning true
	 * if the collection contains the named key.
	 * @param string the property name
	 * @return boolean whether the property is defined
	 */
	public function hasProperty($name)
	{
		$name=strtolower($name);
		return $this->contains($name) || parent::hasProperty($name);
	}

	/**
	 * Determines whether a property can be read.
	 * This method overrides parent implementation by returning true
	 * if the collection contains the named key.
	 * @param string the property name
	 * @return boolean whether the property can be read
	 */
	public function canGetProperty($name)
	{
		$name=strtolower($name);
		return $this->contains($name) || parent::canGetProperty($name);
	}

	/**
	 * Determines whether a property can be set.
	 * This method overrides parent implementation by always returning true
	 * because you can always add a new value to the collection.
	 * @param string the property name
	 * @return boolean true
	 */
	public function canSetProperty($name)
	{
		return true;
	}
}

?>