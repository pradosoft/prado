<?php

/**
 *
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Id$
 * @package System.Data.SqlMap.Statements
 * @since 3.1
 */
class TCachingStatement implements IMappedStatement
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

	public function executeQueryForMap($connection, $parameter,$keyProperty, $valueProperty=null, $delegate=null)
	{
		$sql = $this->createCommand($connection, $parameter);
		$key = $this->getCacheKey(array($sql, $keyProperty, $valueProperty));
		$map = $this->getStatement()->getCache()->get($key);
		if(is_null($map))
		{
			$map = $this->_mappedStatement->runQueryForMap(
				$connection, $parameter, $sql, $keyProperty, $valueProperty, $delegate);
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

	public function executeQueryForList($connection, $parameter, $result=null, $skip=-1, $max=-1, $delegate=null)
	{
		$sql = $this->createCommand($connection, $parameter);
		$key = $this->getCacheKey(array($sql, $skip, $max));
		$list = $this->getStatement()->getCache()->get($key);
		if(is_null($list))
		{
			$list = $this->_mappedStatement->runQueryForList(
				$connection, $parameter, $sql, $result, $skip, $max, $delegate);
			$this->getStatement()->getCache()->set($key, $list);
		}
		return $list;
	}

	public function executeQueryForObject($connection, $parameter, $result=null)
	{
		$sql = $this->createCommand($connection, $parameter);
		$key = $this->getCacheKey($sql);
		$object = $this->getStatement()->getCache()->get($key);
		if(is_null($object))
		{
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

	protected function createCommand($connection, $parameter)
	{
		return $this->_mappedStatement->getCommand()->create($this->getManager(),
					$connection, $this->getStatement(), $parameter);
	}
}

?>