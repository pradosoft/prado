<?php
/**
 * TInsertMappedStatement class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\Statements
 */

namespace Prado\Data\SqlMap\Statements;

use Prado\Data\SqlMap\DataMapper\TSqlMapExecutionException;

/**
 * TInsertMappedStatement class.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Statements
 * @since 3.1
 */
class TInsertMappedStatement extends TMappedStatement
{
	public function executeQueryForMap(
		$connection,
		$parameter,
		$keyProperty,
		$valueProperty = null,
		$skip = -1,
		$max = -1,
		$delegate = null
	) {
		throw new TSqlMapExecutionException(
			'sqlmap_cannot_execute_query_for_map',
			get_class($this),
			$this->getID()
		);
	}

	public function executeUpdate($connection, $parameter)
	{
		throw new TSqlMapExecutionException(
			'sqlmap_cannot_execute_update',
			get_class($this),
			$this->getID()
		);
	}

	public function executeQueryForList(
		$connection,
		$parameter,
		$result = null,
		$skip = -1,
		$max = -1,
		$delegate = null
	) {
		throw new TSqlMapExecutionException(
			'sqlmap_cannot_execute_query_for_list',
			get_class($this),
			$this->getID()
		);
	}

	public function executeQueryForObject($connection, $parameter, $result = null)
	{
		throw new TSqlMapExecutionException(
			'sqlmap_cannot_execute_query_for_object',
			get_class($this),
			$this->getID()
		);
	}
}
