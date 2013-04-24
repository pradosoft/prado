<?php
/**
 * TActiveRecordCriteria class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id: TActiveRecordCriteria.php 3245 2013-01-07 20:23:32Z ctrlaltca $
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
 * @version $Id: TActiveRecordCriteria.php 3245 2013-01-07 20:23:32Z ctrlaltca $
 * @package System.Data.ActiveRecord
 * @since 3.1
 */
class TActiveRecordCriteria extends TSqlCriteria
{

}

