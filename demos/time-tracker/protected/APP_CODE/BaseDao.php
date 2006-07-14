<?php

class BaseDao
{
	private $_connection;
	
	public function setConnection($connection)
	{
		$this->_connection = $connection;
	}
	
	protected function getConnection()
	{
		return $this->_connection;
	}
}

?>