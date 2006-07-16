<?php

class TInsertMappedStatement extends TMappedStatement
{
	public function executeQueryForMap($connection, $parameter, 
								$keyProperty, $valueProperty=null)
	{
		throw new TSqlMapExecutionException(
				'sqlmap_cannot_execute_query_for_map', get_class($this), $this->getID());
	}

	public function executeUpdate($connection, $parameter)
	{
		throw new TSqlMapExecutionException(
				'sqlmap_cannot_execute_update', get_class($this), $this->getID());
	}

	public function executeQueryForList($connection, $parameter, $result,
										$skip=-1, $max=-1)
	{
		throw new TSqlMapExecutionException(
				'sqlmap_cannot_execute_query_for_list', get_class($this), $this->getID());
	}

	public function executeQueryForObject($connection, $parameter, $result)
	{
		throw new TSqlMapExecutionException(
				'sqlmap_cannot_execute_query_for_object', get_class($this), $this->getID());
	}
}

?>