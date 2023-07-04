<?php
/**
 * TActiveRecordCriteria class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\ActiveRecord;

use Prado\Data\DataGateway\TSqlCriteria;

/**
 * Search criteria for Active Record.
 *
 * Criteria object for active record finder methods. Usage:
 * ```php
 * $criteria = new TActiveRecordCriteria;
 * $criteria->Condition = 'username = :name AND password = :pass';
 * $criteria->Parameters[':name'] = 'admin';
 * $criteria->Parameters[':pass'] = 'prado';
 * $criteria->OrdersBy['level'] = 'desc';
 * $criteria->OrdersBy['name'] = 'asc';
 * $criteria->Limit = 10;
 * $criteria->Offset = 20;
 * ```
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TActiveRecordCriteria extends TSqlCriteria
{
}
