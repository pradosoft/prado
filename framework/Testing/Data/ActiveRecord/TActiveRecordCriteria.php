<?php
/**
 * TActiveRecordCriteria class file
 * Adapts *deprecated* Prado TActiveRecordCriteria to TDbCriteria (Yii)
 * @class TActiveRecordCriteria
 * @deprecated
 * @author Daniel Mueller <tux@penguinfriends.org>
 */

Prado::using('System.Testing.Data.Schema.TDbCriteria');

class TSqlCriteria extends TDbCriteria
{
	
	private $_TDbCriteria = null;

	public function __construct($condition=null, $parameters=array())
	{
		parent::__construct();
		if(!is_array($parameters) && func_num_args() > 1)
			$parameters = array_slice(func_get_args(), 1);

		$this->_TDbCriteria = $this;
		
		$this->_TDbCriteria->params = new ArrayObject();

		$param = array(
			'condition' => ($condition !== null) ? $condition : '',
			'params' => $parameters
		);
	}

	public function getCondition()
	{
		return $this->_TDbCriteria->condition;
	}

	public function setCondition($value)
	{
		if(empty($value))
			return;

		// supporting the following SELECT-syntax:
		// [ORDER BY {col_name | expr | position}
		//      [ASC | DESC], ...]
		//    [LIMIT {[offset,] row_count | row_count OFFSET offset}]
		// See: http://dev.mysql.com/doc/refman/5.0/en/select.html
				
				
		if(preg_match('/ORDER\s+BY\s+(.*?)(?=LIMIT)|ORDER\s+BY\s+(.*?)$/i', $value, $matches) > 0) {
			// condition contains ORDER BY
			$value = str_replace($matches[0], '', $value);
			if(strlen($matches[1]) > 0) {
				$this->_TDbCriteria->setOrdersBy($matches[1]);
			} else if(strlen($matches[2]) > 0) {
				$this->setOrdersBy($matches[2]);
			}
		}
			
		if(preg_match('/LIMIT\s+([\d\s,]+)/i', $value, $matches) > 0) {
				// condition contains limit
			$value = str_replace($matches[0], '', $value); 
			// remove limit from query
			if(strpos($matches[1], ',')) { // both offset and limit given
				list($offset, $limit) = explode(',', $matches[1]);
				$this->_TDbCriteria->limit = (int)$limit;
				$this->_TDbCriteria->offset = (int)$offset;
			} else { // only limit given
				$this->_TDbCriteria->limit = (int)$matches[1];
			}
		}

		if(preg_match('/OFFSET\s+(\d+)/i', $value, $matches) > 0) {
			// condition contains offset
			$value = str_replace($matches[0], '', $value); 
			// remove offset from query
			$this->_TDbCriteria->offset = (int)$matches[1]; // set offset in criteria
		}
		
		$this->_TDbCriteria->condition = trim($value);
	}

	public function getParameters()
	{
		return $this->_TDbCriteria->params;
	}

	public function setParameters($value)
	{
		if(is_array($value))
		{
			$this->_TDbCriteria->params = $value;
		}
		elseif ($value instanceof ArrayAccess)
		{
			$this->_TDbCriteria->params = (array)$value;
		}
		else
			throw new TException('Value must be an array or of type ArrayAccess.');
	}

	public function getIsNamedParameters()
	{
		foreach($this->_TDbCriteria->params as $key=>$val)
			return is_string($key);
	}

	public function getLimit()
	{
		return ($this->_TDbCriteria->limit != -1) ? $this->_TDbCriteria->limit : null;
	}

	public function setLimit($value)
	{
		$this->_TDbCriteria->limit = (int)$value;
	}

	public function getOffset()
	{
		return ($this->_TDbCriteria->offset != -1) ? $this->_TDbCriteria->offset : null;
	}

	public function setOffset($value)
	{
		$this->_TDbCriteria->offset = (int)$value;
	}
	
	public function getOrdersBy()
	{
		if(empty($this->_TDbCriteria->order))
			return array();

		$value=trim(preg_replace('/\s+/',' ', $this->_TDbCriteria->order));
		$orderBys=array();
		foreach(explode(',',$value) as $orderBy)
		{
			$vs=explode(' ',trim($orderBy));
			
		$orderBys[$vs[0]]=isset($vs[1])?$vs[1]:'asc';
		}
		return $orderBys;
	}

	public function setOrdersBy($value)
	{
		if(is_string($value))
			$this->TDbCriteria->order = $value;
		elseif (is_array($value) || $value instanceof Traversable)
		{
			$str = '';
			foreach($value as $key => $val)
				$str .= (($str != '') ? ', ': '').$key.' '.$val;
			
			$this->_TDbCriteria->order = $str;
		}
	}	

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
		if($this->getLimit() !==null)
			$str .= ', '.$this->_limit;
		if($this->getOffset() !== null)
			$str .= ', '.$this->_offset;
		return $str;
	}

	/**
	 * Returns a property value or an event handler list by property or event name.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using the following syntax to read a property:
	 * <code>
	 * $value=$component->PropertyName;
	 * </code>
	 * and to obtain the event handler list for an event,
	 * <code>
	 * $eventHandlerList=$component->EventName;
	 * </code>
	 * @param string the property name or the event name
	 * @return mixed the property value or the event handler list
	 * @throws TInvalidOperationException if the property/event is not defined.
	 */
	public function __get($name)
	{
		$getter='get'.$name;
		if(method_exists($this,$getter))
		{
			// getting a property
			return $this->$getter();
		}
		else if(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
		{
			// getting an event (handler list)
			$name=strtolower($name);
			if(!isset($this->_e[$name]))
				$this->_e[$name]=new TList;
			return $this->_e[$name];
		}
		else
		{
			throw new TInvalidOperationException('component_property_undefined',get_class($this),$name);
		}
	}

	/**
	 * Sets value of a component property.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using the following syntax to set a property or attach an event handler.
	 * <code>
	 * $this->PropertyName=$value;
	 * $this->EventName=$handler;
	 * </code>
	 * @param string the property name or event name
	 * @param mixed the property value or event handler
	 * @throws TInvalidOperationException If the property is not defined or read-only.
	 */
	public function __set($name,$value)
	{
		$setter='set'.$name;
		if(method_exists($this,$setter))
		{
			$this->$setter($value);
		}
		else if(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
		{
			$this->attachEventHandler($name,$value);
		}
		else if(method_exists($this,'get'.$name))
		{
			throw new TInvalidOperationException('component_property_readonly',get_class($this),$name);
		}
		else
		{
			throw new TInvalidOperationException('component_property_undefined',get_class($this),$name);
		}
	}

}

class TActiveRecordCriteria extends TSqlCriteria
{
	
}
