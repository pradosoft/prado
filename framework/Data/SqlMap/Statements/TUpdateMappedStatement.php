<?php

/**
 * TUpdateMappedStatement class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Data\SqlMap\Statements;

use Prado\Data\SqlMap\DataMapper\TSqlMapExecutionException;

/**
 * TUpdateMappedStatement class.
 *
 * @author Wei Zhuo <weizho[at]gmail[dot]com>
 * @since 3.1
 */
class TUpdateMappedStatement extends TMappedStatement
{
	public function executeInsert($connection, $parameter)
	{
		throw new TSqlMapExecutionException(
			'sqlmap_cannot_execute_insert',
			$this::class,
			$this->getID()
		);
	}

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
			$this::class,
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
			$this::class,
			$this->getID()
		);
	}

	public function executeQueryForObject($connection, $parameter, $result = null)
	{
		throw new TSqlMapExecutionException(
			'sqlmap_cannot_execute_query_for_object',
			$this::class,
			$this->getID()
		);
	}
}
