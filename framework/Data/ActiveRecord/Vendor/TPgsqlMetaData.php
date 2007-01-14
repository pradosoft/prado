<?php
/**
 * TPgsqlMetaData class file.
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
 * TPgsqlMetaData class.
 *
 * Command builder for Postgres database
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.ActiveRecord.Vendor
 * @since 3.1
 */
class TPgsqlMetaData extends TDbMetaDataCommon
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
			$sql .= ' WHERE '.$condition;
		$orders=array();
		foreach($criteria->getOrdersBy() as $by=>$ordering)
			$orders[] = $this->getOrdering($by, $ordering);
		if(count($orders) > 0)
			$sql .= ' ORDER BY '.implode(', ', $orders);
		if(($limit = $criteria->getLimit())!==null)
			$sql .= ' LIMIT '.intval($limit); //assumes integer limit?
		if(($offset = $criteria->getOffset())!==null)
			$sql .= ' OFFSET '.intval($offset); //assumes integer offset?
		return strlen($sql) > 0 ? $sql : '';
	}

	protected function getOrdering($by, $direction)
	{
		$dir = strtolower($direction) == 'desc' ? 'DESC' : 'ASC';
		return $this->getColumn($by)->getName(). ' '.$dir;
	}

}
?>