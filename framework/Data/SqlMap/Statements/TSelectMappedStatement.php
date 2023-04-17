<?php
/**
 * TSelectMappedStatement class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\SqlMap\Statements;

use Prado\Data\SqlMap\DataMapper\TSqlMapExecutionException;

/**
 * TSelectMappedStatment class.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TSelectMappedStatement extends TMappedStatement
{
	public function executeInsert($connection, $parameter)
	{
		throw new TSqlMapExecutionException(
			'sqlmap_cannot_execute_insert',
			$this::class,
			$this->getID()
		);
	}

	public function executeUpdate($connection, $parameter)
	{
		throw new TSqlMapExecutionException(
			'sqlmap_cannot_execute_update',
			$this::class,
			$this->getID()
		);
	}
}
