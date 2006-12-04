<?php

class TUpdateMappedStatement extends TMappedStatement
{
	public function executeInsert($connection, $parameter)
	{
		throw new TSqlMapExecutionException(
				'sqlmap_cannot_execute_insert', get_class($this), $this->getID());
	}

	public function executeQueryForMap($connection, $parameter, $keyProperty,
											$valueProperty=null)
	{
		throw new TSqlMapExecutionException(
				'sqlmap_cannot_execute_query_for_map', get_class($this), $this->getID());
	}

	public function executeQueryForList($connection, $parameter, $result=null,
										$skip=-1, $max=-1)
	{
		throw new TSqlMapExecutionException(
				'sqlmap_cannot_execute_query_for_list', get_class($this), $this->getID());
	}

	public function executeQueryForObject($connection, $parameter, $result=null)
	{
		throw new TSqlMapExecutionException(
				'sqlmap_cannot_execute_query_for_object', get_class($this), $this->getID());
	}
}

?>