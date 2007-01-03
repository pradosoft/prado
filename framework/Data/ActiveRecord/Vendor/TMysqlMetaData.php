<?php
/**
 * TMysqlMetaData class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2007 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 */

Prado::using('System.Data.ActiveRecord.Vendor.TDbMetaDataCommon');

/**
 * TMysqlMetaData specialized command builder for Mysql database.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 * @since 3.1
 */
class TMysqlMetaData extends TDbMetaDataCommon
{
	/**
	 * Build the SQL search string from the criteria object for Postgress database.
	 * @param TDbConnection database connection.
	 * @param TActiveRecordCriteria search criteria.
	 * @return string SQL search.
	 */
	protected function getSqlFromCriteria($conn, TActiveRecordCriteria $criteria)
	{
		$sql = '';
		if(($condition = $criteria->getCondition())!==null)
			$sql .= $condition;
		$orders=array();
		foreach($criteria->getOrdersBy() as $by=>$ordering)
			$orders[] = $conn->quoteString($by).' '.$this->getOrdering($ordering);
		if(count($orders) > 0)
			$sql .= ' ORDER BY '.implode(', ', $orders);
		if(($limit = $criteria->getLimit())!==null)
		{
			$offset = $criteria->getOffset();
			$offset = $offset===null?0:intval($offset); //assumes integer offset
			$sql .= ' LIMIT '.$offset.', '.intval($limit); //assumes integer limit
		}
		return strlen($sql) > 0 ? ' WHERE '.$sql : '';
	}

	private function getOrdering($direction)
	{
		if(strtolower($direction)=='desc')
			return 'DESC';
		else
			return 'ASC';
	}
}

?>