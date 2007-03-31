<?php

/**
 * TIbmMetaData class file.
 *
 * @author Cesar Ramos <cramos[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 */
Prado::using('System.Data.ActiveRecord.Vendor.TDbMetaDataCommon');

/**
 * TIbmMetaData class.
 *
 * Column details for IBM DB2 database. Using php_pdo_ibm.dll extension.
 *
 * Does not support LIMIT and OFFSET criterias.
 *
 * @author Cesar Ramos <cramos[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 * @since 3.1
 */
class TIbmMetaData extends TDbMetaDataCommon
{
	/**
	 * Build the SQL search string from the criteria object for IBM DB2 database.
	 * @param TDbConnection database connection.
	 * @param TActiveRecordCriteria search criteria.
	 * @return string SQL search.
	 */
	protected function getSqlFromCriteria($conn, $criteria)
	{
		if($criteria===null) return '';
		$sql = '';
		if(($condition = $criteria->getCondition())!==null)
			$sql .= ' WHERE '.$condition;
		$orders=array();
		foreach($criteria->getOrdersBy() as $by=>$ordering)
			$orders[] = $this->getOrdering($by, $ordering);
		if(count($orders) > 0)
			$sql .= ' ORDER BY '.implode(', ', $orders);
		//if(($limit = $criteria->getLimit())!==null)
		//{
		//	$sql .= ' FETCH FIRST '.intval($limit).' ROWS ONLY';
		//}
		return strlen($sql) > 0 ? $sql : '';
	}

	/**
	 * Lowercase the data keys, IBM DB2 returns uppercase column names
	 * @param mixed record row
	 * @return array record row
	 */
	public function postQueryRow($row)
	{
		if(!is_array($row)) return $row;
		$result=array();
		foreach($row as $k=>$v)
			$result[strtolower($k)]=$v;
		return $result;
	}

	/**
	 * Lowercase the data keys, IBM DB2 returns uppercase column names
	 * @param mixed record row
	 * @return array record row
	 */
	public function postQuery($rows)
	{
		$data = array();
		foreach($rows as $k=>$v)
			$data[$k] = $this->postQueryRow($v);
		return $data;
	}
	
	public function getSearchRegExpCriteria($fields, $keywords)
	{
		if(strlen(trim($keywords)) == 0) return '';
		$words = array();
		preg_match_all('/([a-zA-Z0-9-+]+)/', $keywords, $words);
		$result = array();
		foreach($fields as $field)
		{
			$column = $this->getColumn($field);
			if($this->isSearchableColumn($column))
				$result[] = $this->getLikeCriteriaStr($column->getName(), $words[0]);
		}
			$a = '('.implode(' OR ', $result).')';
			error_log($a);
		return '('.implode(' OR ', $result).')';
	}

	protected function isSearchableColumn($column)
	{
		$type = strtolower($column->getType());
		return $type === 'char' || $type === 'varchar';
	}

	protected function getLikeCriteriaStr($column, $words)
	{
		$result=array();
		foreach($words as $word)
			$result[] = "{$column} LIKE '%{$word}%'";
		return '('.implode(' AND ', $result).')';
	}

	
}
?>