<?php
/**
 * TCachingStatement class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Data\SqlMap\Statements
 */

namespace Prado\Data\SqlMap\Statements;

use Prado\Data\SqlMap\Configuration\TSqlMapCacheKey;

/**
 * TCacheingStatement class.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Data\SqlMap\Statements
 * @since 3.1
 */
class TCachingStatement extends \Prado\TComponent implements IMappedStatement
{
	private $_mappedStatement;

	public function __construct(TMappedStatement $statement)
	{
		$this->_mappedStatement = $statement;
	}

	public function getID()
	{
		return $this->_mappedStatement->getID();
	}

	public function getStatement()
	{
		return $this->_mappedStatement->getStatement();
	}

	public function getManager()
	{
		return $this->_mappedStatement->getManager();
	}

	public function executeQueryForMap($connection, $parameter, $keyProperty, $valueProperty = null, $skip = -1, $max = -1, $delegate = null)
	{
		$sql = $this->createCommand($connection, $parameter, $skip, $max);
		$key = $this->getCacheKey([clone($sql), $keyProperty, $valueProperty, $skip, $max]);
		$map = $this->getStatement()->getCache()->get($key);
		if ($map === null) {
			$map = $this->_mappedStatement->runQueryForMap(
				$connection,
				$parameter,
				$sql,
				$keyProperty,
				$valueProperty,
				$delegate
			);
			$this->getStatement()->getCache()->set($key, $map);
		}
		return $map;
	}

	public function executeUpdate($connection, $parameter)
	{
		return $this->_mappedStatement->executeUpdate($connection, $parameter);
	}

	public function executeInsert($connection, $parameter)
	{
		return $this->executeInsert($connection, $parameter);
	}

	public function executeQueryForList($connection, $parameter, $result = null, $skip = -1, $max = -1, $delegate = null)
	{
		$sql = $this->createCommand($connection, $parameter, $skip, $max);
		$key = $this->getCacheKey([clone($sql), $parameter, $skip, $max]);
		$list = $this->getStatement()->getCache()->get($key);
		if ($list === null) {
			$list = $this->_mappedStatement->runQueryForList(
				$connection,
				$parameter,
				$sql,
				$result,
				$delegate
			);
			$this->getStatement()->getCache()->set($key, $list);
		}
		return $list;
	}

	public function executeQueryForObject($connection, $parameter, $result = null)
	{
		$sql = $this->createCommand($connection, $parameter);
		$key = $this->getCacheKey([clone($sql), $parameter]);
		$object = $this->getStatement()->getCache()->get($key);
		if ($object === null) {
			$object = $this->_mappedStatement->runQueryForObject($connection, $sql, $result);
			$this->getStatement()->getCache()->set($key, $object);
		}
		return $object;
	}

	protected function getCacheKey($object)
	{
		$cacheKey = new TSqlMapCacheKey($object);
		return $cacheKey->getHash();
	}

	protected function createCommand($connection, $parameter, $skip = null, $max = null)
	{
		return $this->_mappedStatement->getCommand()->create(
			$this->getManager(),
			$connection,
			$this->getStatement(),
			$parameter,
			$skip,
			$max
		);
	}
}
