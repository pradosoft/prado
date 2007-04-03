<?php
/**
 * TActiveRecordCriteria class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord
 */

/**
 * Search criteria for Active Record.
 *
 * Criteria object for active record finder methods. Usage:
 * <code>
 * $criteria = new TActiveRecordCriteria;
 * $criteria->Condition = 'username = :name AND password = :pass';
 * $criteria->Parameters[':name'] = 'admin';
 * $criteria->Parameters[':pass'] = 'prado';
 * $criteria->OrdersBy['level'] = 'desc';
 * $criteria->OrdersBy['name'] = 'asc';
 * $criteria->Limit = 10;
 * $criteria->Offset = 20;
 * </code>
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord
 * @since 3.1
 */
class TActiveRecordCriteria extends TComponent
{
	private $_condition;
	private $_parameters;
	private $_ordersBy;
	private $_limit;
	private $_offset;

	/**
	 * Creates a new criteria with given condition;
	 * @param string sql string after the WHERE stanza
	 * @param mixed named or indexed parameters, accepts as multiple arguments.
	 */
	public function __construct($condition=null,$parameters=array())
	{
		if(!is_array($parameters) && func_num_args() > 2)
		{
			$parameters = func_get_args();
			array_shift($parameters);
		}
		$this->setCondition($condition);
		$this->_parameters=new TAttributeCollection((array)$parameters);
		$this->_ordersBy=new TAttributeCollection;
	}

	/**
	 * @return TAttributeCollection list of named parameters and values.
	 */
	public function getParameters()
	{
		return $this->_parameters;
	}

	/**
	 * @param ArrayAccess named parameters.
	 */
	public function setParameters($value)
	{
		if(!(is_array($value) || $value instanceof ArrayAccess))
			throw new TException('value must be array or ArrayAccess');
		$this->_parameters->copyFrom($value);
	}

	/**
	 * @return boolean true if the parameter index are string base, false otherwise.
	 */
	public function getIsNamedParameters()
	{
		foreach($this->getParameters() as $k=>$v)
			return is_string($k);
	}

	/**
	 * @return TAttributeCollection ordering clause.
	 */
	public function getOrdersBy()
	{
		return $this->_ordersBy;
	}

	/**
	 * @param ArrayAccess ordering clause.
	 */
	public function setOrdersBy($value)
	{
		if(!(is_array($value) || $value instanceof ArrayAccess))
			throw new TException('value must be array or ArrayAccess');
		$this->_ordersBy->copyFrom($value);
	}

	/**
	 * @return string search conditions.
	 */
	public function getCondition()
	{
		return $this->_condition;
	}

	/**
	 * Sets the search conditions to be placed after the WHERE clause in the SQL.
	 * @param string search conditions.
	 */
	public function setCondition($value)
	{
		$this->_condition=$value;
	}

	/**
	 * @return int maximum number of records to return.
	 */
	public function getLimit()
	{
		return $this->_limit;
	}

	/**
	 * @param int maximum number of records to return.
	 */
	public function setLimit($value)
	{
		$this->_limit=$value;
	}

	/**
	 * @return int record offset.
	 */
	public function getOffset()
	{
		return $this->_offset;
	}

	/**
	 * @param int record offset.
	 */
	public function setOffset($value)
	{
		$this->_offset=$value;
	}

	/**
	 * This method is invoked before the object is deleted from the database.
	 * The method raises 'OnDelete' event.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TActiveRecordEventParameter event parameter to be passed to the event handlers
	 */
	public function onDelete($param)
	{
		$this->raiseEvent('OnDelete', $this, $param);
	}

	/**
	 * This method is invoked before any select query is executed on the database.
	 * The method raises 'OnSelect' event.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TActiveRecordEventParameter event parameter to be passed to the event handlers
	 */
	public function onSelect($param)
	{
		$this->raiseEvent('OnSelect', $this, $param);
	}

	/**
	 * @return string string representation of the criteria. Useful for debugging.
	 */
	public function __toString()
	{
		$str = '';
		if(strlen((string)$this->getCondition()) > 0)
			$str .= '"'.(string)$this->getCondition().'"';
		$params = array();
		foreach($this->getParameters() as $k=>$v)
			$params[] = "{$k} => ${v}";
		if(count($params) > 0)
			$str .= ', "'.implode(', ',$params).'"';
		$orders = array();
		foreach($this->getOrdersBy() as $k=>$v)
			$orders[] = "{$k} => ${v}";
		if(count($orders) > 0)
			$str .= ', "'.implode(', ',$orders).'"';
		if($this->_limit !==null)
			$str .= ', '.$this->_limit;
		if($this->_offset !== null)
			$str .= ', '.$this->_offset;
		return $str;
	}
}

?>