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

Prado::using('System.Data.DataGateway.TSqlCriteria');

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
class TActiveRecordCriteria extends TSqlCriteria
{
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
}

?>